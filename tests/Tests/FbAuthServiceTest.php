<?php

use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;

it('returns the mobile notification keys for the mobile flow', function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);

    expect(FbAuth::getResetPasswordNotificationKeys())->toBe([
        'title' => 'fb-auth::fb-auth.reset_password.request.notification.mobile.title',
        'body' => 'fb-auth::fb-auth.reset_password.request.notification.mobile.body',
    ]);
});

it('returns the code notification keys for the code flow', function () {
    config(['fb-auth.auth_type' => AuthType::Code]);

    expect(FbAuth::getResetPasswordNotificationKeys())->toBe([
        'title' => 'fb-auth::fb-auth.reset_password.request.notification.code.title',
        'body' => 'fb-auth::fb-auth.reset_password.request.notification.code.body',
    ]);
});
