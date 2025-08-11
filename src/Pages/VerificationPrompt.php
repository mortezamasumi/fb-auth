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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\VerifyCodeNotification;
use Mortezamasumi\FbAuth\Notifications\VerifyMobileNotification;
use Closure;
use Exception;

class VerificationPrompt extends EmailVerificationPrompt
{
    use WithRateLimiting;

    /** @var array<string, mixed> | null */
    public ?array $data = ['otp' => ''];
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
            ->label(__(match (config('fb-auth.auth_type')) {
                AuthType::Mobile => 'fb-auth::fb-auth.otp.mobile_label',
                AuthType::Code => 'fb-auth::fb-auth.otp.code_label',
            }))
            ->required()
            ->view('fb-auth::otp-input')
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
        return __(match (config('fb-auth.auth_type')) {
            AuthType::Mobile => 'fb-auth::fb-auth.otp.verify_mobile_title',
            AuthType::Code => 'fb-auth::fb-auth.otp.verify_code_title',
        });
    }

    public function getHeading(): string|Htmlable
    {
        return __(match (config('fb-auth.auth_type')) {
            AuthType::Mobile => 'fb-auth::fb-auth.otp.verify_mobile_title',
            AuthType::Code => 'fb-auth::fb-auth.otp.verify_code_title',
        });
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
            ->label(__('fb-auth::fb-auth.verify.prompt.action.label'))
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
            match (config('fb-auth.auth_type')) {
                AuthType::Code => VerifyCodeNotification::class,
                AuthType::Mobile => VerifyMobileNotification::class,
            },
            [
                'code' => FbAuth::createCode($user)
            ]
        );

        /** @var Notifiable $user */
        $user->notify($notification);

        $this->getSentNotification()?->send();

        redirect(Filament::getCurrentPanel()->getEmailVerificationPromptUrl());
    }

    protected function getSentNotification(): ?Notification
    {
        switch (config('fb-auth.auth_type')) {
            case AuthType::Mobile:
                $title = 'fb-auth::fb-auth.verify.prompt.notification.mobile.title';
                $body = 'fb-auth::fb-auth.verify.prompt.notification.mobile.body';
                break;
            case AuthType::Code:
                $title = 'fb-auth::fb-auth.verify.prompt.notification.code.title';
                $body = 'fb-auth::fb-auth.verify.prompt.notification.code.body';
                break;
        }

        return Notification::make()
            ->title(__($title))
            ->body(__($body))
            ->success();
    }
}
