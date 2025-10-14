<?php

use Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Hash;
use Mortezamasumi\FbAuth\Enums\AuthType;
use Mortezamasumi\FbAuth\Pages\Login;
use Mortezamasumi\FbAuth\Pages\Register;
use Mortezamasumi\FbAuth\Tests\Services\User;

beforeEach(function () {
    config(['fb-auth.auth_type' => AuthType::Code]);
});

it('can redirect from login page to registeration page', function () {
    $this
        ->livewire(Login::class)
        ->assertActionExists('register')
        ->assertActionHasUrl('register', filament()->getRegistrationUrl());
});

it('can render registeration page', function () {
    $this
        ->livewire(Register::class)
        ->assertSuccessful();
});

it('can get validation error', function () {
    $this
        ->livewire(Register::class)
        ->call('register')
        ->assertHasFormErrors([
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
});

it('can register user', function () {
    config(['auth.providers.users.model' => User::class]);

    $formData = [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'email' => fake()->email(),
        'password' => 'password123',
        'passwordConfirmation' => 'password123',
    ];

    $this
        ->livewire(Register::class)
        ->fillForm($formData)
        ->call('register')
        ->assertHasNoFormErrors()
        ->assertRedirect(Dashboard::getUrl());

    $this->assertDatabaseHas('users', [
        'first_name' => $formData['first_name'],
        'last_name' => $formData['last_name'],
        'email' => $formData['email'],
    ]);

    $user = User::where('email', $formData['email'])->first();

    $this->assertTrue(Hash::check($formData['password'], $user->password));

    $this->assertAuthenticatedAs($user);
});
