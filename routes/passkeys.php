<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\Auth\PasskeyLoginController;
use Brackets\AdminAuth\Http\Controllers\PasskeyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'guest.admin:' . config('admin-auth.defaults.guard')])
    ->prefix('/admin/login/passkey')
    ->name('brackets/admin-auth::admin/')
    ->group(static function (): void {
        Route::post('/options', [PasskeyLoginController::class, 'options'])
            ->name('login/passkey/options');
        Route::post('/', [PasskeyLoginController::class, 'login'])
            ->name('login/passkey');
    });

// The admin group's EnsureTwoFactorAuthenticated lets a forced admin without a factor reach these by route name.
Route::middleware(['web', 'auth:' . config('admin-auth.defaults.guard'), 'admin'])
    ->prefix('/admin/passkeys')
    // Not {passkey}: laravel/passkeys binds that parameter name globally to its own model.
    ->name('brackets/admin-auth::admin/')
    ->group(static function (): void {
        Route::get('/', [PasskeyController::class, 'index'])
            ->name('passkeys');
        Route::post('/options', [PasskeyController::class, 'options'])
            ->name('passkeys/options');
        Route::post('/', [PasskeyController::class, 'store'])
            ->name('passkeys/store');
        Route::patch('/{adminPasskey}', [PasskeyController::class, 'update'])
            ->whereNumber('adminPasskey')
            ->name('passkeys/update');
        Route::delete('/{adminPasskey}', [PasskeyController::class, 'destroy'])
            ->whereNumber('adminPasskey')
            ->name('passkeys/destroy');
    });
