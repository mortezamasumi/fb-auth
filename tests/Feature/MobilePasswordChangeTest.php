<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Facades\FbAuth;
use Mortezamasumi\FbAuth\Notifications\PasswordResetMobileNotification;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Pages\RequestPasswordReset;
use Mortezamasumi\FbAuth\Pages\ResetPassword;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);
});

it('can redirect from login page to request page', function () {
    $this
        ->livewire(Login::class)
        ->assertSeeText(__('filament-panels::auth/pages/login.actions.request_password_reset.label'))
        ->assertSee(filament()->getRequestPasswordResetUrl());
});

it('can render request page', function () {
    $this
        ->livewire(RequestPasswordReset::class)
        ->assertSuccessful();
});

it('can get validation error', function () {
    $this
        ->livewire(RequestPasswordReset::class)
        ->call('request')
        ->assertHasFormErrors([
            'mobile' => 'required',
        ]);
});

it('can redirect on request', function () {
    $user = User::factory()->create();

    $this
        ->livewire(RequestPasswordReset::class)
        ->fillForm(['mobile' => $user->mobile])
        ->call('request')
        ->assertHasNoFormErrors()
        ->assertSuccessful();
});

it('can send the password change notification', function () {
    Notification::fake();
    Event::fake();

    $user = User::factory()->unverified()->create();

    $this
        ->livewire(RequestPasswordReset::class)
        ->fillForm(['mobile' => $user->mobile])
        ->call('request');

    Notification::assertSentTo($user, PasswordResetMobileNotification::class);

    Event::assertDispatched(PasswordResetLinkSent::class);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->mobile,
    ]);
});

return;
it('can reset the password with a valid code', function () {
    Notification::fake();
    Event::fake();

    $user = User::factory()->create();
    $oldPasswordHash = $user->password;

    $code = FbAuth::createCode($user);

    $token = app('auth.password.broker')->createToken($user);

    $this
        ->livewire(ResetPassword::class, [
            'token' => $token,
            'email' => $user->email,
        ])
        ->fillForm([
            'otp' => $code,
            'password' => $newPassword = 'new-strong-password',
            'passwordConfirmation' => $newPassword,
        ])
        ->call('resetPassword')
        ->assertHasNoFormErrors();

    Event::assertDispatched(PasswordReset::class);

    $this->assertGuest();

    $user->refresh();

    expect($user->password)->not->toBe($oldPasswordHash);

    $this->assertTrue(Hash::check($newPassword, $user->password));

    $this->assertDatabaseMissing('password_reset_tokens', [
        'email' => $user->email,
    ]);
});

it('shows a validation error if the token is invalid', function () {
    Event::fake();

    $user = User::factory()->create();

    $code = FbAuth::createCode($user);

    $this
        ->livewire(ResetPassword::class, [
            'token' => 'this-is-not-a-valid-token',
            'email' => $user->email,
        ])
        ->fillForm([
            'otp' => $code,
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
        ])
        ->call('resetPassword');

    Event::assertNotDispatched(PasswordReset::class);
});

it('get validation error on invalid code', function () {
    Event::fake();

    $user = User::factory()->create();

    $this
        ->livewire(ResetPassword::class, [
            'token' => 'token-is-not-affecting-here',
            'email' => $user->email,
        ])
        ->fillForm([
            'otp' => fake()->numerify('####'),
            'password' => 'new-strong-password',
            'passwordConfirmation' => 'new-strong-password',
        ])
        ->call('resetPassword')
        ->assertHasFormErrors(['otp']);

    Event::assertNotDispatched(PasswordReset::class);
});
