<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;

final class MissingRoutesController extends Controller
{
    /**
     * Display default admin home page
     */
    public function redirect(Redirector $redirector): RedirectResponse
    {
        return $redirector->route('brackets/admin-auth::admin/login');
    }
}
