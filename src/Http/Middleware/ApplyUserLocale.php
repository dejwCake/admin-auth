<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ApplyUserLocale
{
    /**
     * Guard used for admin user
     */
    private string $guard;

    /**
     * ApplyUserLocale constructor.
     */
    public function __construct(private readonly Config $config, private readonly AuthFactory $authFactory)
    {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->authFactory->guard($this->guard)->check()) {
            $user = $this->authFactory->guard($this->guard)->user();
            if ($user instanceof Model && $user->hasAttribute('language') && $user->language !== null) {
                app()->setLocale($user->language);
            }
        }

        return $next($request);
    }
}
