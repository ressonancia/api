<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

if (! config('ressonance.self_hosted')) {
    Route::middleware(['auth:api'])->group(function () {
        Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::post('/change-password', [SettingsController::class, 'changePassword'])
            ->name('api.password.change');

        Route::post('/logout', [LogoutController::class, 'logout'])->name('api.logout');
    });

    Route::middleware(['guest'])->group(function () {
        Route::post('/forgot-password', [ResetPasswordController::class, 'send'])
            ->name('password.email');

        Route::post('reset-password', [ResetPasswordController::class, 'reset'])
            ->name('password.reset');
    });

    Route::post('/account', [AccountController::class, 'store'])->name('api.account.store');
}
