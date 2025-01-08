<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectIfAuthenticated
{
    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    /**
     * RedirectIfAuthenticated constructor.
     */
    public function __construct()
    {
        $this->guard = config('admin-auth.defaults.guard');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        if (Auth::guard($guard)->check()) {
            return $guard === $this->guard ? redirect(config('admin-auth.login_redirect')) : redirect('/home');
        }

        return $next($request);
    }
}
