<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\UnauthorizedException;

final class CanAdmin
{
    /**
     * Guard used for admin user
     */
    private string $guard;

    /**
     * CanAdmin constructor.
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
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->authFactory->guard($this->guard)->check()) {
            $user = $this->authFactory->guard($this->guard)->user();
            if ($user instanceof Authorizable && $user->can('admin')) {
                return $next($request);
            }
        }

        if (!$this->authFactory->guard($this->guard)->check()) {
            return $this->redirector->guest('/admin/login');
        } else {
            throw new UnauthorizedException('Unauthorized');
        }
    }
}
