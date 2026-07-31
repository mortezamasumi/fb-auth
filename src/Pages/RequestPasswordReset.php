<?php

namespace Mortezamasumi\FbAuth\Pages;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Schema;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Exceptions\AuthTypeException;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\PasswordResetCodeNotification;
use Mortezamasumi\FbAuth\Notifications\PasswordResetMobileNotification;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('mobile')
                    ->label(__('fb-auth::fb-auth.form.mobile'))
                    ->required()
                    ->tel()
                    ->rules([
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $provider = Auth::getProvider();

                            if (! $provider instanceof EloquentUserProvider) {
                                return;
                            }

                            $userModel = $provider->getModel();

                            $user = $userModel::where('mobile', $value)
                                ->where('active', true)
                                ->where(function (Builder $query) {
                                    $query
                                        ->whereDate('expiration_date', '>', now())
                                        ->orWhere('expiration_date', null);
                                })
                                ->first();

                            if (! $user) {
                                $fail(__('filament-panels::auth/pages/login.messages.failed'));
                            }
                        },
                    ])
                    ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
                    ->maxLength(30)
                    ->toEN()
                    ->visible(config('fb-auth.auth_type') === AuthType::Mobile),
                TextInput::make('email')
                    ->label(__('filament-panels::auth/pages/register.form.email.label'))
                    ->required()
                    ->rules([
                        'email',
                        fn (): Closure => function (string $attribute, $value, Closure $fail) {
                            $provider = Auth::getProvider();

                            if (! $provider instanceof EloquentUserProvider) {
                                return;
                            }

                            $userModel = $provider->getModel();

                            $user = $userModel::where('email', $value)
                                ->where('active', true)
                                ->where(function (Builder $query) {
                                    $query
                                        ->whereDate('expiration_date', '>', now())
                                        ->orWhere('expiration_date', null);
                                })
                                ->first();

                            if (! $user) {
                                $fail(__('filament-panels::auth/pages/login.messages.failed'));
                            }
                        },
                    ])
                    ->extraAttributes(['dir' => 'ltr'])
                    ->maxLength(255)
                    ->toEN()
                    ->hidden(config('fb-auth.auth_type') === AuthType::Mobile),
            ]);
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('request')
            ->label(__(
                config('fb-auth.auth_type') === AuthType::Mobile
                    ? 'fb-auth::fb-auth.reset_password.request.action.mobile'
                    : 'fb-auth::fb-auth.reset_password.request.action.email'
            ))
            ->submit('request');
    }

    public function request(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data = $this->form->getState();

        /** @var PasswordResetCodeNotification|PasswordResetMobileNotification|null $notification */
        $notification = null;

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token) use (&$notification): void {
                /** @var Panel $panel */
                $panel = Filament::getCurrentOrDefaultPanel();

                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel($panel))
                ) {
                    return;
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

                /** @var AuthType $authType */
                $authType = config('fb-auth.auth_type');

                $notification = app(
                    match ($authType) {
                        AuthType::Code => PasswordResetCodeNotification::class,
                        AuthType::Mobile => PasswordResetMobileNotification::class,
                        default => throw new AuthTypeException,
                    },
                    [
                        'token' => $token,
                        'code' => $user instanceof Model ? FbAuth::createCode($user) : null,
                    ]
                );

                $mobile = $user instanceof Model ? $user->getAttribute('mobile') : null;

                $notification->url = Filament::getResetPasswordUrl(
                    $token,
                    $user,
                    $authType === AuthType::Mobile ? ['mobile' => $mobile] : []
                );

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }

                \Illuminate\Support\Facades\Notification::send($user, $notification);
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->getSentNotification($status)?->send();

        if ($notification === null) {
            return;
        }

        redirect($notification->url);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        return match ($authType) {
            AuthType::Code => ['email' => $data['email']],
            AuthType::Mobile => ['mobile' => $data['mobile']],
            default => throw new AuthTypeException,
        };
    }

    protected function getSentNotification(string $status): ?Notification
    {
        $keys = FbAuth::getResetPasswordNotificationKeys();

        $notification = Notification::make()
            ->title(__($keys['title']))
            ->success();

        if ($status === Password::RESET_LINK_SENT) {
            $notification->body(__($keys['body']));
        }

        return $notification;
    }
}
