<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\UnauthorizedException;

class CanAdmin
{
    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    /**
     * CanAdmin constructor.
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
        if (Auth::guard($this->guard)->check() && Auth::guard($this->guard)->user()->can('admin')) {
            return $next($request);
        }

        if (!Auth::guard($this->guard)->check()) {
            return redirect()->guest('/admin/login');
        } else {
            throw new UnauthorizedException('Unauthorized');
        }
    }
}
