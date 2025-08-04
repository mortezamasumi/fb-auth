<?php

namespace Mortezamasumi\FbAuth;

use Exception;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\MobileLogin;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Mortezamasumi\FbAuth\Pages\UsernameLogin;

class FbAuthPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-auth';
    }

    public function register(Panel $panel): void
    {
        $this->checkSetup();

        $this->registerAuthType();

        if (config('app.auth_tye') === AuthType::Link) {
            if ($panel->hasEmailVerification()) {
                $panel->emailChangeVerification();
            }

            return;
        }

        if ($panel->hasLogin() && config('app.auth_tye') === AuthType::User) {
            $panel
                ->login(UsernameLogin::class)
                ->emailVerification(null)
                ->passwordReset(null, null);

            return;
        }

        if ($panel->hasLogin() && config('app.auth_tye') === AuthType::Mobile) {
            $panel->login(MobileLogin::class);
        }

        if ($panel->hasRegistration()) {
            $panel->registration(Register::class);
        }

        if ($panel->hasPasswordReset()) {
            $panel->passwordReset(RequestPasswordReset::class, ResetPassword::class);
        }
    }

    protected function registerAuthType(): void
    {
        if (config('fb-auth.email_required')) {
            if (config('fb-auth.email_link_verification')) {
                $type = AuthType::Link;
            } else {
                $type = AuthType::Code;
            }
        } else {
            if (config('fb-auth.email_link_verification')) {
                throw new Exception('Can not use link while auth type are mobile/username');
            }

            if (config('fb-auth.mobile_required')) {
                $type = AuthType::Mobile;
            } else {
                $type = AuthType::User;
            }
        }

        config(['app.auth_type' => $type]);
    }

    protected function checkSetup(): void
    {
        $trueCount = collect(config('fb-auth'))
            ->only(['mobile_required', 'email_required', 'username_required'])
            ->filter()
            ->count();

        if ($trueCount === 1) {
            return;
        }

        throw new Exception('Only and required one of link/code/mobile auth types to be selected');
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
