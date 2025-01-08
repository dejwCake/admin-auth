<?php

namespace Brackets\AdminAuth\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class MissingRoutesController extends Controller
{
    /**
     * Display default admin home page
     */
    public function redirect(): RedirectResponse
    {
        return Redirect::route('brackets/admin-auth::admin/login');
    }
}
