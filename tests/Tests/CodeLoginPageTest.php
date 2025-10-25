<?php

use Filament\Pages\Dashboard;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Code]);
});

it('can render login page', function () {
    $this
        ->livewire(Login::class)
        ->assertSuccessful();
});

it('can see validation error on empty values', function () {
    $this
        ->livewire(Login::class)
        ->call('authenticate')
        ->assertHasFormErrors([
            'email' => 'required',
            'password' => 'required',
        ]);
});

it('can authenticate', function () {
    $user = User::factory()->create();

    $this
        ->livewire(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl());
});

it('can get validation error on no exists user', function () {
    $this
        ->livewire(Login::class)
        ->fillForm([
            'email' => fake()->email(),
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(
            ['email' => __('filament-panels::auth/pages/login.messages.failed')]
        );
});

it('can get validation error on expired user', function () {
    $user = User::factory()->expired()->create();

    $this
        ->livewire(Login::class)
        ->fillForm([
            'email' => $user->email,
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(
            ['email' => __('fb-auth::fb-auth.expiration.message')]
        );
});
