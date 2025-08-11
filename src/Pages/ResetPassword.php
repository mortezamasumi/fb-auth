<?php

namespace Mortezamasumi\FbAuth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\PasswordResetCodeNotification;
use Mortezamasumi\FbAuth\Notifications\PasswordResetMobileNotification;
use Closure;
use Exception;

class ResetPassword extends BaseResetPassword
{
    #[Locked]
    public ?string $mobile = null;

    public ?string $otp = null;

    public function mount(?string $email = null, ?string $token = null): void
    {
        if (Filament::auth()->check()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->token = $token ?? request()->query('token');

        $this->form->fill([
            'email' => $email ?? request()->query('email'),
            'mobile' => request()->query('mobile'),
        ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        $data['mobile'] = $this->mobile;
        $data['email'] = $this->email;
        $data['token'] = $this->token;

        $hasPanelAccess = true;

        $status = Password::broker(Filament::getAuthPasswordBroker())->reset(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword|Model|Authenticatable $user) use ($data, &$hasPanelAccess): void {
                if (
                    ($user instanceof FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    $hasPanelAccess = false;

                    return;
                }

                $user->forceFill([
                    'password' => Hash::make($data['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($hasPanelAccess === false) {
            $status = Password::INVALID_USER;
        }

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title(__($status))
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        }

        Notification::make()
            ->title(__($status))
            ->danger()
            ->send();

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function getCredentialsFromFormData(array $data): array
    {
        switch (config('fb-auth.auth_type')) {
            case AuthType::Mobile:
                unset($data['email']);
                break;
            default:
                unset($data['mobile']);
                break;
        }

        unset($data['otp']);

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getOTPFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getOTPFormComponent(): Component
    {
        return TextInput::make('otp')
            ->label(__(match (config('fb-auth.auth_type')) {
                AuthType::Mobile => 'fb-auth::fb-auth.otp.mobile_label',
                AuthType::Code => 'fb-auth::fb-auth.otp.code_label',
            }))
            ->required()
            ->autocomplete()
            ->autofocus()
            ->rules([
                fn (): Closure => function (string $attribute, $value, Closure $fail) {
                    [$code, $time] = Cache::get('otp-'.match (config('fb-auth.auth_type')) {
                        AuthType::Mobile => $this->mobile,
                        AuthType::Code => $this->email,
                    });

                    if (! $code) {
                        $fail(__('fb-auth::fb-auth.otp.expired'));
                    }

                    if ($value !== $code) {
                        $fail(__('fb-auth::fb-auth.otp.validation'));
                    }
                }
            ])
            ->hintAction(
                Action::make('resend-code')
                    ->label(__('fb-auth::fb-auth.otp.resend_action'))
                    ->view('fb-auth::resend-action')
                    ->action(fn ($state) => $this->resend())
            );
    }

    public function resend(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data['mobile'] = $this->mobile;
        $data['email'] = $this->email;
        $data['token'] = $this->token;

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
                    ['mobile' => $user->mobile]
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

    protected function getFailureNotification(string $status): ?Notification
    {
        return Notification::make()
            ->title(__($status))
            ->danger();
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
