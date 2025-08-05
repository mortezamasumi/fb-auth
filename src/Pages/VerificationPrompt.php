<?php

namespace Mortezamasumi\FbAuth\Pages;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Session;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\VerifyCodeNotification;
use Mortezamasumi\FbAuth\Notifications\VerifyMobileNotification;
use Exception;

class VerificationPrompt extends EmailVerificationPrompt
{
    use WithRateLimiting;

    /** @var array<string, mixed> | null */
    public ?array $data = [];
    public ?string $mobile = null;
    public ?string $email = null;

    public function mount(): void
    {
        if ($this->getVerifiable()->hasVerifiedEmail()) {
            redirect()->intended(Filament::getUrl());
        }

        $this->mobile = $this->getVerifiable()->mobile;
        $this->email = $this->getVerifiable()->email;
    }

    public function verify(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $this->form->getState();

        $this->getVerifiable()->markEmailAsVerified();

        redirect(Filament::getUrl());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getOTPFormComponent(),
            ]);
    }

    protected function getOTPFormComponent(): Component
    {
        return TextInput::make('otp')
            ->label('Enter code sent to mobile/email')
            ->required()
            ->autocomplete()
            ->autofocus()
            ->hintAction(
                Action::make('resend-code')
                    ->label(__('fb-auth::fb-auth.otp.resend-action'))
                    ->view('fb-auth::resend-action')
                    ->action(fn ($state) => $this->resend())
            );
    }

    public function loginAction(): Action
    {
        return Action::make('login')
            ->link()
            ->label(__('filament-panels::auth/pages/password-reset/request-password-reset.actions.login.label'))
            ->icon(match (__('filament-panels::layout.direction')) {
                'rtl' => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login.rtl') ?? Heroicon::ArrowRight,
                default => FilamentIcon::resolve('panels::pages.password-reset.request-password-reset.actions.login') ?? Heroicon::ArrowLeft,
            })
            ->action(function () {
                Filament::auth()->logout();

                Session::invalidate();
                Session::regenerateToken();

                request()->session()->flush();

                return redirect(Filament::getUrl());
            });
    }

    public function getTitle(): string|Htmlable
    {
        return __('Verify otp');
    }

    public function getHeading(): string|Htmlable
    {
        return __('Verify otp');
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            $this->getRequestFormAction(),
        ];
    }

    protected function getRequestFormAction(): Action
    {
        return Action::make('verify-otp')
            ->label(__('verify'))
            ->submit('verify');
    }

    protected function hasFullWidthFormActions(): bool
    {
        return true;
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (! filament()->hasLogin()) {
            return null;
        }

        return $this->loginAction;
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('verify')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->fullWidth($this->hasFullWidthFormActions()),
            ]);
    }

    public function getDefaultTestingSchemaName(): ?string
    {
        return 'form';
    }

    protected function getRateLimitedNotification(TooManyRequestsException $exception): ?Notification
    {
        return Notification::make()
            ->title(__('filament-panels::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled.title', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]))
            ->body(array_key_exists('body', __('filament-panels::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled') ?: []) ? __('filament-panels::auth/pages/email-verification/email-verification-prompt.notifications.notification_resend_throttled.body', [
                'seconds' => $exception->secondsUntilAvailable,
                'minutes' => $exception->minutesUntilAvailable,
            ]) : null)
            ->danger();
    }

    public function resend(): void
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $user = $this->getVerifiable();

        if (! method_exists($user, 'notify')) {
            $userClass = $user::class;

            throw new Exception("Model [{$userClass}] does not have a [notify()] method.");
        }

        $notification = app(
            match (config('app.auth_type')) {
                AuthType::Code => VerifyCodeNotification::class,
                AuthType::Mobile => VerifyMobileNotification::class,
            },
            [
                'code' => FbAuth::createCode($user)
            ]
        );

        /** @var Notifiable $user */
        $user->notify($notification);

        // $this->getSentNotification($status)?->send();

        redirect(Filament::getCurrentPanel()->getEmailVerificationPromptUrl());
    }
}
