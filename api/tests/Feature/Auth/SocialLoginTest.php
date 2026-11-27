<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(array $overrides = []): void
{
    Socialite::fake('google', SocialiteUser::fake(array_merge([
        'id' => 'google-123',
        'name' => 'Sara Ali',
        'email' => 'sara@gmail.com',
        'email_verified' => true,
    ], $overrides)));
}

function googleCallback(array $payload = []): TestResponse
{
    return test()->postJson('/api/v1/auth/google/callback', array_merge([
        'code' => 'auth-code',
        'deviceName' => 'web',
    ], $payload));
}

it('returns the Google consent URL', function () {
    fakeGoogleUser();

    $this->getJson('/api/v1/auth/google/redirect')
        ->assertOk()
        ->assertJsonPath('data.url', 'https://socialite.fake/google/authorize');
});

it('returns 404 for a provider that is not supported', function () {
    $this->getJson('/api/v1/auth/github/redirect')->assertNotFound();
});

it('creates a new user without a password on first sign in', function () {
    fakeGoogleUser();

    googleCallback()
        ->assertOk()
        ->assertJsonPath('data.user.email', 'sara@gmail.com')
        ->assertJsonStructure(['data' => ['token']]);

    $user = User::sole();
    expect($user->password)->toBeNull()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->socialAccounts()->sole()->provider_user_id)->toBe('google-123');
});

it('signs in the same user on the next sign in', function () {
    $account = SocialAccount::factory()->create(['provider_user_id' => 'google-123']);
    fakeGoogleUser(['email' => 'changed-at-google@gmail.com']);

    googleCallback()->assertOk()->assertJsonPath('data.user.id', $account->user_id);

    expect(User::count())->toBe(1)->and(SocialAccount::count())->toBe(1);
});

it('links Google to an existing account with the same email', function () {
    $user = User::factory()->create(['email' => 'sara@gmail.com']);
    fakeGoogleUser();

    googleCallback()->assertOk()->assertJsonPath('data.user.id', $user->id);

    expect($user->socialAccounts()->count())->toBe(1);
});

it('rejects an email Google has not verified', function () {
    User::factory()->create(['email' => 'sara@gmail.com']);
    fakeGoogleUser(['email_verified' => false]);

    googleCallback()->assertUnprocessable();

    expect(SocialAccount::count())->toBe(0);
});

it('returns 401 when Google rejects the code', function () {
    Socialite::fake('google', fn () => throw new RuntimeException('invalid_grant'));

    googleCallback()
        ->assertUnauthorized()
        ->assertJsonPath('message', 'Could not sign in with this provider. Please try again.');
});

it('requires the code and device name', function () {
    $this->postJson('/api/v1/auth/google/callback')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'deviceName']);
});

it('does not let a Google-only user sign in with a password', function () {
    fakeGoogleUser();
    googleCallback()->assertOk();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'sara@gmail.com',
        'password' => '',
        'deviceName' => 'web',
    ])->assertUnprocessable();

    $this->postJson('/api/v1/auth/login', [
        'email' => 'sara@gmail.com',
        'password' => 'anything',
        'deviceName' => 'web',
    ])->assertUnauthorized();
});
