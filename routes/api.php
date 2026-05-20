<?php

use App\Http\Controllers\AccountController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('too-much-secret', function () {
//     phpinfo();
// });

Route::middleware(['auth:api', 'verified'])->group(function () {
    Route::delete('/delete-account', [AccountController::class, 'destroy'])->name('api.users.destroy');
});

Route::middleware(['auth:api'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->name('api.me');
});
