<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\AdminHomepageController;
use Brackets\AdminAuth\Http\Controllers\Auth\ActivationController;
use Brackets\AdminAuth\Http\Controllers\Auth\ForgotPasswordController;
use Brackets\AdminAuth\Http\Controllers\Auth\LoginController;
use Brackets\AdminAuth\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['web'])
    ->prefix('/admin')
    ->name('brackets/admin-auth::admin/')
    ->group(static function (): void {
        Route::get('/login', [LoginController::class, 'showLoginForm'])
            ->name('login');
        Route::post('/login', [LoginController::class, 'login']);

        Route::any('/logout', [LoginController::class, 'logout'])
            ->name('logout');

        Route::prefix('/password-reset')
            ->name('password/')
            ->group(static function (): void {
                Route::get('/', [ForgotPasswordController::class, 'showLinkRequestForm'])
                    ->name('show-forgot-form');
                Route::post('/send', [ForgotPasswordController::class, 'sendResetLinkEmail']);
                Route::get('/{token}', [ResetPasswordController::class, 'showResetForm'])
                    ->name('show-reset-form');
                Route::post('/reset', [ResetPasswordController::class, 'reset']);
            });

        Route::get('/admin/activation/{token}', [ActivationController::class, 'activate'])
            ->name('activation/activate');
    });

Route::middleware(['web', 'admin', 'auth:' . config('admin-auth.defaults.guard')])
    ->group(static function (): void {
        Route::get('/admin', [AdminHomepageController::class, 'index'])
            ->name('brackets/admin-auth::admin');
    });
