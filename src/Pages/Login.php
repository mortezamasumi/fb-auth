<?php

namespace Mortezamasumi\FbAuth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\MultiFactor\Contracts\HasBeforeChallengeHook;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use Mortezamasumi\FbAuth\Enums\AuthType;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this
                    ->getMobileFormComponent()
                    ->visible(config('fb-auth.auth_type') === AuthType::Mobile),
                $this
                    ->getUsernameFormComponent()
                    ->visible(config('fb-auth.auth_type') === AuthType::User),
                $this
                    ->getEmailFormComponent()
                    ->visible(
                        config('fb-auth.auth_type') === AuthType::Code ||
                        config('fb-auth.auth_type') === AuthType::Link
                    ),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ]);
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('fb-auth::fb-auth.form.username'))
            ->required()
            ->maxLength(255);
    }

    protected function getMobileFormComponent(): Component
    {
        return TextInput::make('mobile')
            ->label(__('fb-auth::fb-auth.form.mobile'))
            ->required()
            ->tel()
            ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
            ->maxLength(30)
            ->toEN();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        $key = match (config('fb-auth.auth_type')) {
            AuthType::Mobile => 'mobile',
            AuthType::Code => 'email',
            AuthType::Link => 'email',
            AuthType::User => 'username',
        };

        return [
            $key => $data[$key],
            'password' => $data['password'],
        ];
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        /** @var SessionGuard $authGuard */
        $authGuard = Filament::auth();

        $authProvider = $authGuard->getProvider();
        /** @phpstan-ignore-line */
        $credentials = $this->getCredentialsFromFormData($data);

        $user = $authProvider->retrieveByCredentials($credentials);

        if ((! $user) || (! $authProvider->validateCredentials($user, $credentials))) {
            $this->userUndertakingMultiFactorAuthentication = null;

            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if (
            filled($this->userUndertakingMultiFactorAuthentication) &&
            (decrypt($this->userUndertakingMultiFactorAuthentication) === $user->getAuthIdentifier())
        ) {
            $this->multiFactorChallengeForm->validate();
        } else {
            foreach (Filament::getMultiFactorAuthenticationProviders() as $multiFactorAuthenticationProvider) {
                if (! $multiFactorAuthenticationProvider->isEnabled($user)) {
                    continue;
                }

                $this->userUndertakingMultiFactorAuthentication = encrypt($user->getAuthIdentifier());

                if ($multiFactorAuthenticationProvider instanceof HasBeforeChallengeHook) {
                    $multiFactorAuthenticationProvider->beforeChallenge($user);
                }

                break;
            }

            if (filled($this->userUndertakingMultiFactorAuthentication)) {
                $this->multiFactorChallengeForm->fill();

                return null;
            }
        }

        if (
            ! $authGuard->attemptWhen($credentials, function (Authenticatable $user): bool {
                if (! ($user instanceof FilamentUser)) {
                    return true;
                }

                return $user->canAccessPanel(Filament::getCurrentOrDefaultPanel());
            }, $data['remember'] ?? false)
        ) {
            $this->fireFailedEvent($authGuard, $user, $credentials);
            $this->throwFailureValidationException();
        }

        if ($user->expiration_date && $user->expiration_date->isPast()) {
            Filament::auth()->logout();

            $this->throwFailureExpirationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    protected function throwFailureValidationException(): never
    {
        $key = match (config('fb-auth.auth_type')) {
            AuthType::Mobile => 'mobile',
            AuthType::Code => 'email',
            AuthType::Link => 'email',
            AuthType::User => 'username',
        };

        throw ValidationException::withMessages([
            'data.'.$key => __('filament-panels::auth/pages/login.messages.failed'),
        ]);
    }

    protected function throwFailureExpirationException(): never
    {
        $key = match (config('fb-auth.auth_type')) {
            AuthType::Mobile => 'mobile',
            AuthType::Code => 'email',
            AuthType::Link => 'email',
            AuthType::User => 'username',
        };

        throw ValidationException::withMessages([
            'data.'.$key => __('fb-auth::fb-auth.expiration.message'),
        ]);
    }
}
