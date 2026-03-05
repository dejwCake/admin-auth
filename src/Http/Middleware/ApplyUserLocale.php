<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ApplyUserLocale
{
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly AuthFactory $authFactory,
        private readonly Application $app,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->authFactory->guard($this->guard)->check()) {
            $user = $this->authFactory->guard($this->guard)->user();
            if ($user instanceof Model && $user->hasAttribute('language') && $user->language !== null) {
                $this->app->setLocale($user->language);
            }
        }

        return $next($request);
    }
}
