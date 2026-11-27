<?php

namespace App\Actions\Auth;

use App\Enums\SocialProvider;
use App\Exceptions\Auth\SocialAuthenticationFailedException;
use App\Exceptions\Auth\SocialEmailNotVerifiedException;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\DB;
use Laravel\Socialite\Contracts\User as ProviderUser;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * Signs a user in with an OAuth provider authorization code.
 *
 * 1. Already linked account → sign in that user.
 * 2. Existing user with the same (verified) email → link the account, then sign in.
 * 3. Otherwise → create a new user without a password, link, sign in.
 */
final class AuthenticateWithSocialProvider
{
    public function __construct(private readonly AuthService $auth) {}

    /**
     * @return array{user: User, token: string}
     *
     * @throws SocialAuthenticationFailedException
     * @throws SocialEmailNotVerifiedException
     */
    public function handle(SocialProvider $provider, string $deviceName): array
    {
        $providerUser = $this->fetchProviderUser($provider);

        $user = DB::transaction(fn (): User => $this->findOrCreateUser($provider, $providerUser));

        return $this->auth->issueToken($user, $deviceName);
    }

    /**
     * Exchange the authorization code (read from the request) for the provider profile.
     * Stateless because the API has no session; the frontend validates the OAuth state.
     */
    private function fetchProviderUser(SocialProvider $provider): ProviderUser
    {
        try {
            return Socialite::driver($provider->value)->stateless()->user();
        } catch (Throwable $e) {
            throw new SocialAuthenticationFailedException($e);
        }
    }

    private function findOrCreateUser(SocialProvider $provider, ProviderUser $providerUser): User
    {
        $linkedUser = User::whereRelation('socialAccounts', fn ($query) => $query
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUser->getId())
        )->first();

        if ($linkedUser) {
            return $linkedUser;
        }

        if (! $this->emailIsVerified($providerUser)) {
            throw new SocialEmailNotVerifiedException;
        }

        $user = User::firstOrCreate(
            ['email' => $providerUser->getEmail()],
            ['name' => $providerUser->getName() ?? $providerUser->getEmail()],
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_user_id' => $providerUser->getId(),
        ]);

        return $user;
    }

    /**
     * Never trust an email the provider has not verified: otherwise anyone could
     * register that address at the provider and take over the local account.
     */
    private function emailIsVerified(ProviderUser $providerUser): bool
    {
        return $providerUser->getEmail() !== null
            && filter_var($providerUser->getRaw()['email_verified'] ?? false, FILTER_VALIDATE_BOOL);
    }
}
