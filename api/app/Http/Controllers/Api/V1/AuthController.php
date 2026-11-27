<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\AuthTokenResource;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Email and password authentication with Sanctum personal access tokens.
 *
 * Docs: docs/api/authentication.md
 */
final class AuthController extends Controller
{
    public function __construct(private readonly AuthService $service) {}

    /**
     * Create an account and return a token for the given device (201).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->service->register($request->prepareForDatabase());

        return (new AuthTokenResource($result))->response()->setStatusCode(201);
    }

    /**
     * Return a new token for the given device, or 401 on wrong credentials.
     */
    public function login(LoginRequest $request): AuthTokenResource
    {
        return new AuthTokenResource($this->service->login($request->prepareForDatabase()));
    }

    /**
     * The user who owns the token sent with the request.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Revoke the token sent with the request; other devices stay signed in.
     */
    public function logout(Request $request): JsonResponse
    {
        $this->service->logout($request->user());

        return response()->json(['message' => __('auth.logged_out')]);
    }
}
