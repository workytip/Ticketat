<?php

namespace App\Services;

use App\Exceptions\Auth\InvalidCredentialsException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

final class AuthService
{
    /**
     * @param  array{name: string, email: string, password: string, device_name: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        return $this->issueToken($user, $data['device_name']);
    }

    /**
     * @param  array{email: string, password: string, device_name: string}  $data
     * @return array{user: User, token: string}
     *
     * @throws InvalidCredentialsException
     */
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new InvalidCredentialsException;
        }

        return $this->issueToken($user, $data['device_name']);
    }

    /**
     * Revoke only the token used for this request, so other devices stay signed in.
     */
    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    /**
     * @return array{user: User, token: string}
     */
    public function issueToken(User $user, string $deviceName): array
    {
        return [
            'user' => $user,
            'token' => $user->createToken($deviceName)->plainTextToken,
        ];
    }
}
