<?php

use Filament\Auth\Notifications\VerifyEmail;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Link]);
});

it('allows a verified user to access protected pages', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(Dashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeLivewire(Dashboard::class)
        ->assertDontSeeLivewire(EmailVerificationPrompt::class);
});

it('redirect to send verification email page', function () {
    $this
        ->actingAs(User::factory()->unverified()->create())
        ->get(Dashboard::getUrl())
        ->assertRedirect(Filament::getEmailVerificationPromptUrl());
});

it('can resend the verification email from the prompt page', function () {
    Notification::fake();

    $this
        ->actingAs($user = User::factory()->unverified()->create())
        ->livewire(EmailVerificationPrompt::class)
        ->assertActionExists('resendNotification')
        ->callAction('resendNotification');

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('can verify a user when they click the verification link', function () {
    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    Event::fake();

    $verificationUrl = URL::temporarySignedRoute(
        'filament.admin.auth.email-verification.verify',
        now()->addMinutes(60),
        [
            'id' => $user->getKey(),
            'hash' => sha1($user->getEmailForVerification()),
        ]
    );

    $this
        ->actingAs($user)
        ->get($verificationUrl)
        ->assertRedirect(Dashboard::getUrl());

    Event::assertDispatched(Verified::class);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});
