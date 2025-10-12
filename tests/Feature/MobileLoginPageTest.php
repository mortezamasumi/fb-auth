<?php

use Filament\Pages\Dashboard;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Mobile]);
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
            'mobile' => 'required',
            'password' => 'required',
        ]);
});

it('can authenticate', function () {
    $user = User::factory()->create();

    $this
        ->livewire(Login::class)
        ->fillForm([
            'mobile' => $user->mobile,
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
            'mobile' => fake()->numerify('09#########'),
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(
            ['mobile' => __('filament-panels::auth/pages/login.messages.failed')]
        );
});

it('can get validation error on invalid mobile format', function () {
    $this
        ->livewire(Login::class)
        ->fillForm([
            'mobile' => fake()->numerify('88#########'),
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(
            ['mobile' => 'The mobile field format is invalid.']
        );
});
