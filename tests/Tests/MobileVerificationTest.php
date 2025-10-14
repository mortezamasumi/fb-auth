<?php

use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Pages\Dashboard;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Notifications\VerifyCodeNotification;
use Mortezamasumi\FbAuth\Notifications\VerifyMobileNotification;
use Mortezamasumi\FbAuth\Pages\VerificationPrompt;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);
});

it('allows a verified user to access protected pages', function () {
    $this
        ->actingAs(User::factory()->create())
        ->get(Dashboard::getUrl())
        ->assertSuccessful()
        ->assertSeeLivewire(Dashboard::class)
        ->assertDontSeeLivewire(EmailVerificationPrompt::class);
});

it('redirect to verification code page', function () {
    $this
        ->actingAs(User::factory()->unverified()->create())
        ->get(Dashboard::getUrl())
        ->assertRedirect(Filament::getEmailVerificationPromptUrl());
});

it('can resend the verification code from the prompt page', function () {
    Notification::fake();

    $this
        ->actingAs($user = User::factory()->unverified()->create())
        ->livewire(VerificationPrompt::class)
        ->assertActionExists(TestAction::make('resend-code')->schemaComponent('otp'))
        ->callAction(TestAction::make('resend-code')->schemaComponent('otp'));

    Notification::assertSentTo($user, VerifyMobileNotification::class);
});

it('can verify a user when they enter correct code', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    Event::fake();

    $form = $this
        ->actingAs($user)
        ->livewire(VerificationPrompt::class);

    $form->callAction(TestAction::make('resend-code')->schemaComponent('otp'));

    Notification::assertSentTo(
        $user,
        VerifyMobileNotification::class,
        function (VerifyMobileNotification $notification, array $channels, User $notifiable) use (&$code) {
            $code = $notification->getCode();
            expect($channels)->toContain('sms');

            return true;
        }
    );

    $form
        ->fillForm([
            'otp' => $code,
        ])
        ->call('verify');

    Event::assertDispatched(Verified::class);

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('can fail on incorrect code', function () {
    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $this
        ->actingAs($user)
        ->livewire(VerificationPrompt::class)
        ->fillForm([
            'otp' => fake()->numerify('####'),
        ])
        ->call('verify')
        ->assertHasFormErrors(['otp' => 'Code is not correct']);
});

it('can fail on expired code', function () {
    config(['fb-auth.otp_expiration' => 0]);

    $user = User::factory()->unverified()->create();

    expect($user->hasVerifiedEmail())->toBeFalse();

    $this
        ->actingAs($user)
        ->livewire(VerificationPrompt::class)
        ->fillForm([
            'otp' => fake()->numerify('####'),
        ])
        ->call('verify')
        ->assertHasFormErrors(['otp' => 'Code has been expired, request to resend']);
});
