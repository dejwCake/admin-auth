<?php

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
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $guard = null)
    {
        if (Auth::guard($guard)->check()) {
            if ($guard === $this->guard) {
                return redirect(config('admin-auth.login_redirect'));
            } else {
                return redirect('/home');
            }
        }

        return $next($request);
    }
}
