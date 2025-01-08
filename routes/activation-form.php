<?php

declare(strict_types=1);

use Brackets\AdminAuth\Http\Controllers\Auth\ActivationEmailController;
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
    ->prefix('/admin/activation')
    ->group(static function (): void {
        Route::get('/', [ActivationEmailController::class, 'showLinkRequestForm'])
            ->name('brackets/admin-auth::admin/activation');
        Route::post('/send', [ActivationEmailController::class, 'sendActivationEmail']);
    });
