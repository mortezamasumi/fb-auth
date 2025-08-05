<?php

namespace Mortezamasumi\FbAuth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Exceptions\AuthTypeException;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Mortezamasumi\FbAuth\Pages\VerificationPrompt;
use Exception;

class FbAuthPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-auth';
    }

    public function register(Panel $panel): void
    {
        $this->checkConfig();

        $this->registerAuthType();

        if ($panel->hasLogin()) {
            $panel->login(Login::class);
        }

        if ($panel->hasRegistration()) {
            $panel->registration(Register::class);
        }

        switch (config('app.auth_type')) {
            case AuthType::Link:
                if ($panel->hasEmailVerification()) {
                    $panel->emailChangeVerification();
                }
                break;

            case AuthType::User:
                $panel
                    ->emailVerification(null)
                    ->passwordReset(null, null);
                break;

            default:
                if ($panel->hasPasswordReset()) {
                    $panel->passwordReset(RequestPasswordReset::class, ResetPassword::class);
                }

                if ($panel->hasEmailVerification()) {
                    $panel->emailVerification(VerificationPrompt::class);
                }
                break;
        }
    }

    protected function registerAuthType(): void
    {
        if (config('fb-auth.email_required')) {
            if (config('fb-auth.email_link_verification')) {
                config(['app.auth_type' => AuthType::Link]);
            } else {
                config(['app.auth_type' => AuthType::Code]);
            }
        } else {
            if (config('fb-auth.email_link_verification')) {
                throw new Exception('Can not use link while auth type are mobile/username');
            }

            if (config('fb-auth.mobile_required')) {
                config(['app.auth_type' => AuthType::Mobile]);
            } else {
                config(['app.auth_type' => AuthType::User]);
            }
        }
    }

    protected function checkConfig(): void
    {
        $trueCount = collect(config('fb-auth'))
            ->only(['mobile_required', 'email_required', 'username_required'])
            ->filter()
            ->count();

        throw_unless($trueCount === 1, new AuthTypeException());
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }
}
