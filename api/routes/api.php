<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use Illuminate\Support\Facades\Route;

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
