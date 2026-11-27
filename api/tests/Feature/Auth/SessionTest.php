<?php

use App\Models\User;

it('returns the current user', function () {
    $user = User::factory()->create();
    $token = $user->createToken('web')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('requires a token', function () {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized();
});

it('logs out only the current device', function () {
    $user = User::factory()->create();
    $webToken = $user->createToken('web')->plainTextToken;
    $user->createToken('mobile');

    $this->withToken($webToken)->postJson('/api/v1/auth/logout')->assertOk();

    expect($user->tokens()->pluck('name')->all())->toBe(['mobile']);
});
