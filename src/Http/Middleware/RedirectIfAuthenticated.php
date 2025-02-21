<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final class RedirectIfAuthenticated
{
    /**
     * Guard used for admin user
     */
    private string $guard;

    /**
     * RedirectIfAuthenticated constructor.
     */
    public function __construct(
        private readonly Config $config,
        private readonly AuthFactory $authFactory,
        private readonly Redirector $redirector,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $guard = null): mixed
    {
        if ($this->authFactory->guard($guard)->check()) {
            return $guard === $this->guard
                ? $this->redirector->to($this->config->get('admin-auth.login_redirect'))
                : $this->redirector->to('/home');
        }

        return $next($request);
    }
}
