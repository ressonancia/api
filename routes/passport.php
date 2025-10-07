<?php

use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Http\Controllers\PersonalAccessTokenController;

Route::group([
    'prefix' => 'oauth',
    'as' => 'passport.',
], function () {
    Route::post('/token', [
        'uses' => AccessTokenController::class.'@issueToken',
        'as' => 'token',
        'middleware' => 'throttle',
    ]);

    $guard = config('passport.guard', null);

    Route::middleware(['web', $guard ? 'auth:'.$guard : 'auth'])->group(function () {
        Route::post('/personal-access-tokens', [
            'uses' => [PersonalAccessTokenController::class, 'store'],
            'as' => 'personal.tokens.store',
        ]);

        Route::delete('/personal-access-tokens/{token_id}', [
            'uses' => [PersonalAccessTokenController::class, 'destroy'],
            'as' => 'personal.tokens.destroy',
        ]);
    });
});
