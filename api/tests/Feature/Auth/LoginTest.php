<?php

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'email' => 'sara@example.com',
        'password' => 'Secret-Pass-2026',
    ]);
});

it('logs in with valid credentials', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'sara@example.com',
        'password' => 'Secret-Pass-2026',
        'deviceName' => 'web',
    ])
        ->assertOk()
        ->assertJsonPath('data.user.id', $this->user->id)
        ->assertJsonStructure(['data' => ['token']]);
});

it('rejects a wrong password with 401', function () {
    $this->postJson('/api/v1/auth/login', [
        'email' => 'sara@example.com',
        'password' => 'wrong',
        'deviceName' => 'web',
    ])
        ->assertUnauthorized()
        ->assertExactJson(['message' => 'These credentials do not match our records.']);
});

it('returns the error in Arabic when asked', function () {
    $this->withHeader('Accept-Language', 'ar')
        ->postJson('/api/v1/auth/login', [
            'email' => 'sara@example.com',
            'password' => 'wrong',
            'deviceName' => 'web',
        ])
        ->assertUnauthorized()
        ->assertJsonPath('message', 'بيانات الدخول غير صحيحة.');
});

it('throttles repeated attempts', function () {
    $payload = ['email' => 'sara@example.com', 'password' => 'wrong', 'deviceName' => 'web'];

    foreach (range(1, 5) as $_) {
        $this->postJson('/api/v1/auth/login', $payload)->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', $payload)->assertTooManyRequests();
});
