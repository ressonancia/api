<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\RegisterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('too-much-secret', function () {
//     phpinfo();
// });

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::get('/apps', [AppsController::class, 'index'])->name('api.apps.index');
    Route::get('/apps/{app}', [AppsController::class, 'show'])->name('api.apps.show');
    Route::post('/apps', [AppsController::class, 'store'])->name('api.apps.store');
    Route::delete('/apps/{app}', [AppsController::class, 'destroy'])->name('api.apps.destroy');
    Route::delete('/delete-account', [AccountController::class, 'destroy'])->name('api.users.destroy');
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.me');
});

Route::post('/register', [RegisterController::class, 'store'])
    ->middleware('throttle:api')
    ->name('api.register');
