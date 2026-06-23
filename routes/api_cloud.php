<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\ResetPasswordController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SocialLoginController;
use Illuminate\Support\Facades\Route;

if (! config('ressonance.self_hosted')) {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/organizations/{organization}', [OrganizationController::class, 'show'])
            ->name('api.organizations.show');

        Route::post('/organizations', [OrganizationController::class, 'store'])
            ->name('api.organizations.store');

        Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])
            ->name('api.organizations.destroy');

        Route::patch('/organizations/{organization}', [OrganizationController::class, 'update'])
            ->name('api.organizations.update');

        Route::post('/organizations/{organization}/invitations', [OrganizationInvitationController::class, 'store'])
            ->middleware('throttle:5,1')
            ->name('api.organizations.invitations.store');

        Route::patch('/organization-users/{organizationUser}/role', [OrganizationController::class, 'updateUserRole'])
            ->name('api.organization-users.role.update');

        Route::delete('/organization-users/{organizationUser}', [OrganizationController::class, 'removeUserOrganization'])
            ->name('api.organization-users.destroy');

        Route::post('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::post('/change-password', [SettingsController::class, 'changePassword'])
            ->name('api.password.change');

        Route::patch('/account', [AccountController::class, 'update'])
            ->name('api.account.update');

        Route::post('/logout', [LogoutController::class, 'logout'])->name('api.logout');
    });

    Route::middleware(['guest'])->group(function () {
        Route::post('/forgot-password', [ResetPasswordController::class, 'send'])
            ->name('password.email');

        Route::post('reset-password', [ResetPasswordController::class, 'reset'])
            ->name('password.reset');

        Route::post('/invitations/{invitation}/accept', [OrganizationInvitationController::class, 'accept'])
            ->middleware('signed')
            ->middleware('throttle:5,1')
            ->name('api.invitations.accept');
    });

    Route::post('/account', [AccountController::class, 'store'])->name('api.account.store');

    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('api.social.auth.redirect');

    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('api.social.auth.callback');

    Route::post('/auth/access-token/{authorizationCode}', [SocialLoginController::class, 'getAccessToken'])->name('api.social.auth.access-token');
}
