<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\UnauthorizedException;

final class CanAdmin
{
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly AuthFactory $authFactory,
        private readonly Redirector $redirector,
        private readonly UrlGenerator $urlGenerator,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->authFactory->guard($this->guard)->check()) {
            $user = $this->authFactory->guard($this->guard)->user();
            if ($user instanceof Authorizable && $user->can('admin')) {
                return $next($request);
            }
        }

        if (!$this->authFactory->guard($this->guard)->check()) {
            return $this->redirector->guest(
                $this->urlGenerator->route('brackets/admin-auth::admin/show-login-form'),
            );
        } else {
            throw new UnauthorizedException('Unauthorized');
        }
    }
}
