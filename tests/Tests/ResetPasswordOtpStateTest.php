<?php

use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\ResetPassword;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);
});

it('mounts the reset password page with an empty otp state so the otp input component never receives null', function () {
    /** @var Pest $this */
    $component = $this
        ->livewire(ResetPassword::class, [
            'token' => 'test-token',
            'email' => 'test@example.com',
        ]);

    expect($component->instance()->otp)->toBe('');
});
