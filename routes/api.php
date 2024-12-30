<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\EmailVerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/apps', [AppsController::class, 'index'])->name('api.apps.index');
    Route::get('/apps/{app}', [AppsController::class, 'show'])->name('api.apps.show');
    Route::post('/apps', [AppsController::class, 'store'])->name('api.apps.store');
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.me');

    Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::post('/account', [AccountController::class, 'store'])->name('api.account.store');