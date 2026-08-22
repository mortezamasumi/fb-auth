<?php

namespace Mortezamasumi\FbAuth\Pages;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Exception;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Auth\Pages\PasswordReset\ResetPassword as BaseResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Panel;
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
use Mortezamasumi\FbAuth\Exceptions\AuthTypeException;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\PasswordResetCodeNotification;
use Mortezamasumi\FbAuth\Notifications\PasswordResetMobileNotification;

class ResetPassword extends BaseResetPassword
{
    #[Locked]
    public ?string $mobile = null;

    public ?string $otp = '';

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
                if ($user instanceof FilamentUser) {
                    /** @var Panel $panel */
                    $panel = Filament::getCurrentOrDefaultPanel();

                    if (! $user->canAccessPanel($panel)) {
                        $hasPanelAccess = false;

                        return;
                    }
                }

                $user->forceFill([
                    'password' => Hash::make($data['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                if ($user instanceof Authenticatable) {
                    event(new PasswordReset($user));
                }
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
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        /** @var view-string $otpInputView */
        $otpInputView = 'fb-auth::otp-input';

        /** @var view-string $resendActionView */
        $resendActionView = 'fb-auth::resend-action';

        return TextInput::make('otp')
            ->label(__(match ($authType) {
                AuthType::Mobile => 'fb-auth::fb-auth.otp.mobile_label',
                AuthType::Code => 'fb-auth::fb-auth.otp.code_label',
                default => 'fb-auth::fb-auth.otp.code_label',
            }))
            ->required()
            ->view($otpInputView)
            ->autocomplete()
            ->autofocus()
            ->rules([
                fn (): Closure => function (string $attribute, $value, Closure $fail) use ($authType): void {
                    $otp = Cache::get('otp-'.match ($authType) {
                        AuthType::Mobile => $this->mobile,
                        AuthType::Code => $this->email,
                        default => $this->email,
                    });

                    $code = is_array($otp) ? ($otp[0] ?? null) : null;

                    if (! $code) {
                        $fail(__('fb-auth::fb-auth.otp.expired'));
                    }

                    if ($value !== $code) {
                        $fail(__('fb-auth::fb-auth.otp.validation'));
                    }
                },
            ])
            ->hintAction(
                Action::make('resend-code')
                    ->label(__('fb-auth::fb-auth.otp.resend_action'))
                    ->view($resendActionView)
                    ->action(fn () => $this->resend())
            );
    }

    public function resend(): void
    {
        $notification = null;

        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $data['mobile'] = $this->mobile;
        $data['email'] = $this->email;
        $data['token'] = $this->token;

        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        $status = Password::broker(Filament::getAuthPasswordBroker())->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token) use ($authType, &$notification): void {
                if ($user instanceof FilamentUser) {
                    /** @var Panel $panel */
                    $panel = Filament::getCurrentOrDefaultPanel();

                    if (! $user->canAccessPanel($panel)) {
                        return;
                    }
                }

                if (! method_exists($user, 'notify')) {
                    $userClass = $user::class;

                    throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
                }

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

                $notification->url = Filament::getResetPasswordUrl(
                    $token,
                    $user,
                    ['mobile' => $user instanceof Model ? $user->getAttribute('mobile') : null]
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

    protected function getFailureNotification(string $status): ?Notification
    {
        return Notification::make()
            ->title(__($status))
            ->danger();
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
