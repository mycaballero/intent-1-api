<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Intent-1 API. Versioned under v1. Auth routes are public; protected routes use auth:sanctum.
|
*/

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('forgot-password', [AuthController::class, 'sendResetLink']);
        Route::post('reset-password', [AuthController::class, 'resetPassword']);
    });
});
