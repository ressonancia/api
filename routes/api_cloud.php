<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AppsController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OrganizationInvitationsController;
use App\Http\Controllers\OrganizationsController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SocialLoginController;
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

        Route::post('/organization-invitations/{invitation}/accept', [OrganizationInvitationsController::class, 'accept'])
            ->name('api.organizations.invitations.accept');

        Route::middleware(['verified'])->group(function () {
            Route::scopeBindings()->group(function () {
                Route::get('/organizations/{organization}/apps', [AppsController::class, 'index'])
                    ->name('api.apps.index');
                Route::get('/organizations/{organization}/apps/{app}', [AppsController::class, 'show'])
                    ->name('api.apps.show');
                Route::post('/organizations/{organization}/apps', [AppsController::class, 'store'])
                    ->name('api.apps.store');
                Route::delete('/organizations/{organization}/apps/{app}', [AppsController::class, 'destroy'])
                    ->name('api.apps.destroy');
            });

            Route::patch('/organizations/{organization}', [OrganizationsController::class, 'update'])
                ->name('api.organizations.update');
            Route::delete('/organizations/{organization}', [OrganizationsController::class, 'destroy'])
                ->name('api.organizations.destroy');
            Route::post('/organizations/{organization}/invitations', [OrganizationInvitationsController::class, 'store'])
                ->name('api.organizations.invitations.store');
        });
    });

    Route::middleware(['guest'])->group(function () {
        Route::post('/forgot-password', [ResetPasswordController::class, 'send'])
            ->name('password.email');

        Route::post('reset-password', [ResetPasswordController::class, 'reset'])
            ->name('password.reset');
    });

    Route::post('/account', [AccountController::class, 'store'])->name('api.account.store');

    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('api.social.auth.redirect');

    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('api.social.auth.callback');

    Route::post('/auth/access-token/{authorizationCode}', [SocialLoginController::class, 'getAccessToken'])->name('api.social.auth.access-token');
}
