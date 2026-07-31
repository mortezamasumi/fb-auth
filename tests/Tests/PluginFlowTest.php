<?php

use Filament\Panel;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\FbAuthPlugin;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Mortezamasumi\FbAuth\Pages\VerificationPrompt;

it('wires the custom pages for code and mobile flows', function (AuthType $authType) {
    config(['fb-auth.auth_type' => $authType]);

    $panel = Panel::make()
        ->login()
        ->registration()
        ->emailVerification()
        ->passwordReset();

    FbAuthPlugin::make()->register($panel);

    expect($panel->getLoginRouteAction())->toBe(Login::class)
        ->and($panel->getRegistrationRouteAction())->toBe(Register::class)
        ->and($panel->getEmailVerificationPromptRouteAction())->toBe(VerificationPrompt::class)
        ->and($panel->getRequestPasswordResetRouteAction())->toBe(RequestPasswordReset::class)
        ->and($panel->getResetPasswordRouteAction())->toBe(ResetPassword::class);
})->with([
    'code' => [AuthType::Code],
    'mobile' => [AuthType::Mobile],
]);

it('disables email change verification for the link flow', function () {
    config(['fb-auth.auth_type' => AuthType::Link]);

    $panel = Panel::make()
        ->login()
        ->emailVerification()
        ->emailChangeVerification()
        ->passwordReset();

    FbAuthPlugin::make()->register($panel);

    expect($panel->hasEmailChangeVerification())->toBeFalse();
});

it('disables email verification and password reset for the user flow', function () {
    config(['fb-auth.auth_type' => AuthType::User]);

    $panel = Panel::make()
        ->login()
        ->emailVerification()
        ->passwordReset();

    FbAuthPlugin::make()->register($panel);

    expect($panel->getEmailVerificationPromptRouteAction())->toBeNull()
        ->and($panel->getRequestPasswordResetRouteAction())->toBeNull()
        ->and($panel->getResetPasswordRouteAction())->toBeNull();
});
