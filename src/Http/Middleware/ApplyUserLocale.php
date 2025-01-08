<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApplyUserLocale
{
    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    /**
     * ApplyUserLocale constructor.
     */
    public function __construct()
    {
        $this->guard = config('admin-auth.defaults.guard');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (Auth::guard($this->guard)->check()) {
            $user = Auth::guard($this->guard)->user();
            if (property_exists($user, 'language') && $user->language !== null) {
                app()->setLocale($user->language);
            }
        }

        return $next($request);
    }
}
