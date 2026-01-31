<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpResendController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Auth\OtpVerificationPromptController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\GuestLoginController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:10,60');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,15')
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    // ゲストログインルート（10分間に5回まで制限）
    Route::post('guest-login', [GuestLoginController::class, 'login'])
        ->middleware('throttle:5,10')
        ->name('guest.login');
});

Route::middleware('auth')->group(function () {
    // OTP Email Verification Routes
    Route::get('verify-email', OtpVerificationPromptController::class)
        ->name('verification.notice');

    Route::post('verify-email', OtpVerificationController::class)
        ->middleware('throttle:6,1')
        ->name('verification.verify');

    Route::post('email/verification-notification', [OtpResendController::class, 'store'])
        ->middleware('throttle:5,5')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
