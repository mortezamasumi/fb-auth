<?php

use Filament\Auth\Notifications\ResetPassword as ResetPasswordNotification;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Link]);
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
            'email' => 'required',
        ]);
});

it('can redirect on request', function () {
    $user = User::factory()->create();

    $this
        ->livewire(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request')
        ->assertHasNoFormErrors()
        ->assertSuccessful();
});

it('can send the password change email', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this
        ->livewire(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request');

    Notification::assertSentTo($user, ResetPasswordNotification::class);

    $this->assertDatabaseHas('password_reset_tokens', [
        'email' => $user->email,
    ]);
});

it('can reset the password with a valid token', function () {
    $user = User::factory()->create();
    $oldPasswordHash = $user->password;

    $token = app('auth.password.broker')->createToken($user);

    Event::fake();

    $this
        ->livewire(ResetPassword::class, [
            'token' => $token,
            'email' => $user->email,
        ])
        ->fillForm([
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
    $user = User::factory()->create();
    $invalidToken = 'this-is-not-a-valid-token';

    Event::fake();

    $this
        ->livewire(ResetPassword::class, [
            'token' => $invalidToken,
            'email' => $user->email,
        ])
        ->fillForm([
            'password' => 'new-password',
            'passwordConfirmation' => 'new-password',
        ])
        ->call('resetPassword');

    Event::assertNotDispatched(PasswordReset::class);
});
