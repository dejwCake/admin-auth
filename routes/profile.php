<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:' . config('admin-auth.defaults.guard'), 'admin'])
    ->prefix('/admin')
    ->name('admin/')
    ->controller(ProfileController::class)
    ->group(static function (): void {
        Route::get('/profile', 'editProfile')
            ->name('edit-profile');
        Route::post('/profile', 'updateProfile')
            ->name('update-profile');
        Route::get('/password', 'editPassword')
            ->name('edit-password');
        Route::post('/password', 'updatePassword')
            ->name('update-password');
    });
