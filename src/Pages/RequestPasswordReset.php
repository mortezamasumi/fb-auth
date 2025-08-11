<?php

namespace Mortezamasumi\FbAuth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\PasswordResetCodeNotification;
use Mortezamasumi\FbAuth\Notifications\PasswordResetMobileNotification;
use Exception;

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
                    ->telRegex('/^((\+|00)[1-9][0-9 \-\(\)\.]{11,18}|09\d{9})$/')
                    ->maxLength(30)
                    ->toEN()
                    ->visible(config('fb-auth.auth_type') === AuthType::Mobile),
                TextInput::make('email')
                    ->label(__('filament-panels::auth/pages/register.form.email.label'))
                    ->required()
                    ->rules(['email'])
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

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token) use (&$notification): void {
                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    return;
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

                $notification = app(
                    match (config('fb-auth.auth_type')) {
                        AuthType::Code => PasswordResetCodeNotification::class,
                        AuthType::Mobile => PasswordResetMobileNotification::class,
                    },
                    [
                        'token' => $token,
                        'code' => FbAuth::createCode($user)
                    ]
                );

                $notification->url = Filament::getResetPasswordUrl(
                    $token,
                    $user,
                    match (config('fb-auth.auth_type')) {
                        AuthType::Code => [],
                        AuthType::Mobile => ['mobile' => $user->mobile],
                    }
                );

                /** @var Notifiable $user */
                $user->notify($notification);

                if (class_exists(PasswordResetLinkSent::class)) {
                    event(new PasswordResetLinkSent($user));
                }
            },
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->getFailureNotification($status)?->send();

            return;
        }

        $this->getSentNotification($status)?->send();

        redirect($notification->url);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        return match (config('fb-auth.auth_type')) {
            AuthType::Code => ['email' => $data['email']],
            AuthType::Mobile => ['mobile' => $data['mobile']],
        };
    }

    protected function getSentNotification(string $status): ?Notification
    {
        switch (config('fb-auth.auth_type')) {
            case AuthType::Mobile:
                $title = 'fb-auth::fb-auth.reset_password.request.notification.mobile.title';
                $body = 'fb-auth::fb-auth.reset_password.request.notification.mobile.body';
                break;
            case AuthType::Code:
                $title = 'fb-auth::fb-auth.reset_password.request.notification.code.title';
                $body = 'fb-auth::fb-auth.reset_password.request.notification.code.body';
                break;
        }

        return Notification::make()
            ->title(__($title))
            ->body(($status === Password::RESET_LINK_SENT) ? __($body) : null)
            ->success();
    }
}
