<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\AdminSecurityResetController;
use Illuminate\Support\Facades\Route;

// Under the admin group, so the two-factor gate applies to the acting admin.
Route::middleware(['web', 'auth:' . config('admin-auth.defaults.guard'), 'admin'])
    ->prefix('/admin/admin-users')
    ->name('brackets/admin-auth::admin/')
    ->group(static function (): void {
        // Not {adminUser}: a project or package may bind that name globally to a model.
        Route::post('/{targetAdminId}/security-reset', AdminSecurityResetController::class)
            ->middleware('throttle:6,1,admin-auth-security-reset')
            ->whereNumber('targetAdminId')
            ->name('admin-users/security-reset');
    });
