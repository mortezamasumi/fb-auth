<?php

namespace Mortezamasumi\FbAuth\Pages;

use Closure;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Exception;
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
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Exceptions\AuthTypeException;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\VerifyCodeNotification;
use Mortezamasumi\FbAuth\Notifications\VerifyMobileNotification;

/**
 * @property-read Schema $form
 */
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

        $verifiable = $this->getVerifiable();

        $this->mobile = $verifiable instanceof Model ? $verifiable->getAttribute('mobile') : null;
        $this->email = $verifiable instanceof Model ? $verifiable->getAttribute('email') : null;
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

        event(new Verified($this->getVerifiable()));

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
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        return __(match ($authType) {
            AuthType::Mobile => 'fb-auth::fb-auth.otp.verify_mobile_title',
            AuthType::Code => 'fb-auth::fb-auth.otp.verify_code_title',
            default => 'fb-auth::fb-auth.otp.verify_code_title',
        });
    }

    public function getHeading(): string|Htmlable
    {
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        return __(match ($authType) {
            AuthType::Mobile => 'fb-auth::fb-auth.otp.verify_mobile_title',
            AuthType::Code => 'fb-auth::fb-auth.otp.verify_code_title',
            default => 'fb-auth::fb-auth.otp.verify_code_title',
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

        return $this->loginAction();
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

        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        $notification = app(
            match ($authType) {
                AuthType::Code => VerifyCodeNotification::class,
                AuthType::Mobile => VerifyMobileNotification::class,
                default => throw new AuthTypeException,
            },
            [
                'code' => $user instanceof Model ? FbAuth::createCode($user) : null,
            ]
        );

        \Illuminate\Support\Facades\Notification::send($user, $notification);

        $this->getSentNotification()?->send();

        redirect(Filament::getEmailVerificationPromptUrl());
    }

    protected function getSentNotification(): ?Notification
    {
        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        [$title, $body] = match ($authType) {
            AuthType::Mobile => [
                'fb-auth::fb-auth.verify.prompt.notification.mobile.title',
                'fb-auth::fb-auth.verify.prompt.notification.mobile.body',
            ],
            default => [
                'fb-auth::fb-auth.verify.prompt.notification.code.title',
                'fb-auth::fb-auth.verify.prompt.notification.code.body',
            ],
        };

        return Notification::make()
            ->title(__($title))
            ->body(__($body))
            ->success();
    }
}
