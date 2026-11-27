<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use Illuminate\Support\Facades\Route;

/*
| API routes, served under /api. The version is part of the URL; a new
| version is added only for breaking changes (docs/api/conventions.md).
|
| throttle:auth = 5 attempts per minute per email + IP (AppServiceProvider).
| {provider} is bound to the SocialProvider enum, so unknown providers 404.
*/
Route::prefix('v1')->name('v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:auth')->name('register');
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:auth')->name('login');

        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])->name('social.redirect');
        Route::post('{provider}/callback', [SocialAuthController::class, 'callback'])->middleware('throttle:auth')->name('social.callback');

        Route::middleware('auth:sanctum')->group(function () {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});
