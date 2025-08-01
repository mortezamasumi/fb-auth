<?php

namespace Mortezamasumi\FbAuth;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\EditProfile;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Exception;

class FbAuthPlugin implements Plugin
{
    public function getId(): string
    {
        return 'fb-auth';
    }

    public function register(Panel $panel): void
    {
        $panel
            ->profile(EditProfile::class)
            ->emailChangeVerification();

        if ($panel->hasRegistration()) {
            $panel
                ->registration(Register::class);
        }

        if ($panel->hasPasswordReset()) {
            $panel
                ->passwordReset(RequestPasswordReset::class, ResetPassword::class);
        }
    }

    public function boot(Panel $panel): void
    {
        $values = collect(config('fb-auth'))
            ->only(['mobile_required', 'email_required', 'username_required']);

        $trueCount = count($values->filter());

        if ($trueCount > 1) {
            throw new Exception('Only one of link/code/mobile verification can be select');
        } elseif ($trueCount === 0) {
            throw new Exception('At least one of link/code/mob must be required');
        }

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

                $panel
                    ->emailVerification(null)
                    ->passwordReset(null, null);
            }
        }

        config(['app.auth_type' => $type]);
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
