<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\TwoFactorChallengeController;
use Brackets\AdminAuth\Http\Controllers\TwoFactorController;
use Illuminate\Support\Facades\Route;

// The admin group's EnsureTwoFactorAuthenticated lets unverified or not-yet-enrolled admins reach these by route name.
Route::middleware(['web', 'auth:' . config('admin-auth.defaults.guard'), 'admin'])
    ->prefix('/admin/two-factor')
    ->name('brackets/admin-auth::admin/')
    ->group(static function (): void {
        Route::get('/', [TwoFactorController::class, 'show'])
            ->name('two-factor');
        Route::post('/enable', [TwoFactorController::class, 'enable'])
            ->name('two-factor/enable');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])
            ->name('two-factor/confirm');
        Route::post('/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])
            ->name('two-factor/recovery-codes');
        Route::delete('/', [TwoFactorController::class, 'disable'])
            ->name('two-factor/disable');

        Route::get('/challenge', [TwoFactorChallengeController::class, 'show'])
            ->name('two-factor/challenge');
        Route::post('/challenge', [TwoFactorChallengeController::class, 'verify'])
            ->name('two-factor/verify');
        Route::post('/challenge/passkey/options', [TwoFactorChallengeController::class, 'passkeyOptions'])
            ->name('two-factor/challenge/passkey/options');
        Route::post('/challenge/passkey', [TwoFactorChallengeController::class, 'passkey'])
            ->name('two-factor/challenge/passkey');
    });
