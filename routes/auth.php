<?php

use Convoy\Http\Controllers\Auth\AuthenticatedSessionController;
use Convoy\Http\Controllers\Auth\ConfirmablePasswordController;
use Convoy\Http\Controllers\Auth\EmailVerificationNotificationController;
use Convoy\Http\Controllers\Auth\EmailVerificationPromptController;
use Convoy\Http\Controllers\Auth\NewPasswordController;
use Convoy\Http\Controllers\Auth\PasswordResetLinkController;
use Convoy\Http\Controllers\Auth\RegisteredUserController;
use Convoy\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    /*     Route::get('register', [RegisteredUserController::class, 'create'])
                    ->name('register');

        Route::post('register', [RegisteredUserController::class, 'store']); */

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('/sso/authorize', [AuthenticatedSessionController::class, 'authorizeToken']);

    /*     Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                    ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                    ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                    ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
                    ->name('password.update'); */
});

Route::middleware('auth')->group(function () {
    /*     Route::get('verify-email', [EmailVerificationPromptController::class, '__invoke'])
                    ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', [VerifyEmailController::class, '__invoke'])
                    ->middleware(['signed', 'throttle:6,1'])
                    ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                    ->middleware('throttle:6,1')
                    ->name('verification.send'); */

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
