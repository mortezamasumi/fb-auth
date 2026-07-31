<?php

namespace Mortezamasumi\FbAuth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Mortezamasumi\FbAuth\Pages\VerificationPrompt;

class FbAuthPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-auth';
    }

    public function register(Panel $panel): void
    {
        if ($panel->hasLogin()) {
            $panel->login(Login::class);
        }

        if ($panel->hasRegistration()) {
            $panel->registration(Register::class);
        }

        /** @var AuthType $authType */
        $authType = config('fb-auth.auth_type');

        match ($authType) {
            AuthType::Link => $this->configureLinkFlow($panel),
            AuthType::User => $this->configureUserFlow($panel),
            AuthType::Code, AuthType::Mobile => $this->configureCodeFlow($panel),
        };
    }

    protected function configureLinkFlow(Panel $panel): void
    {
        if ($panel->hasEmailVerification()) {
            $panel->emailChangeVerification(false);
        }
    }

    protected function configureUserFlow(Panel $panel): void
    {
        $panel
            ->emailVerification(null)
            ->passwordReset(null, null);
    }

    protected function configureCodeFlow(Panel $panel): void
    {
        if ($panel->hasPasswordReset()) {
            $panel->passwordReset(RequestPasswordReset::class, ResetPassword::class);
        }

        if ($panel->hasEmailVerification()) {
            $panel
                ->emailVerification(VerificationPrompt::class)
                ->emailChangeVerification(false);
        }
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
