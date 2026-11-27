<?php

use App\Models\User;

function registerPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sara Ali',
        'email' => 'sara@example.com',
        'password' => 'Secret-Pass-2026',
        'passwordConfirmation' => 'Secret-Pass-2026',
        'deviceName' => 'web',
    ], $overrides);
}

it('registers a user and returns a token', function () {
    $this->postJson('/api/v1/auth/register', registerPayload())
        ->assertCreated()
        ->assertJsonPath('data.user.email', 'sara@example.com')
        ->assertJsonPath('data.tokenType', 'Bearer')
        ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email', 'emailVerifiedAt', 'createdAt'], 'token']]);

    expect(User::where('email', 'sara@example.com')->first()->tokens()->first()->name)->toBe('web');
});

it('returns camelCase validation errors', function () {
    $this->postJson('/api/v1/auth/register', registerPayload(['passwordConfirmation' => 'different']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['passwordConfirmation']);
});

it('rejects a duplicate email', function () {
    User::factory()->create(['email' => 'sara@example.com']);

    $this->postJson('/api/v1/auth/register', registerPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('never returns the password', function () {
    $this->postJson('/api/v1/auth/register', registerPayload())
        ->assertJsonMissingPath('data.user.password');
});
