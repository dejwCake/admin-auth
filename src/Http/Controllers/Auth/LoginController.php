<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\AuthenticatesUsers;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */
    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     */
    private string $redirectTo;

    /**
     * Where to redirect users after logout.
     */
    private string $redirectToAfterLogout;

    /**
     * Guard used for admin user
     */
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly ViewFactory $viewFactory,
        private readonly Redirector $redirector,
        private readonly AuthFactory $authFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $this->redirectTo = $this->config->get('admin-auth.login_redirect', '/admin');
        $this->redirectToAfterLogout = $this->config->get('admin-auth.logout_redirect', '/admin/login');
        $this->middleware('guest.admin:' . $this->guard)
            ->except('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm(): View
    {
        return $this->viewFactory->make('brackets/admin-auth::admin.auth.login');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return $this->redirector->to($this->redirectToAfterLogout);
    }

    /**
     * Get the post register / login redirect path.
     */
    public function redirectAfterLogoutPath(): string
    {
        if (method_exists($this, 'redirectToAfterLogout')) {
            return $this->redirectToAfterLogout();
        }

        return $this->redirectToAfterLogout;
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array<string, string|bool>
     */
    private function credentials(Request $request): array
    {
        $conditions = [];
        if (config('admin-auth.check_forbidden')) {
            $conditions['forbidden'] = false;
        }
        if (config('admin-auth.activation_enabled')) {
            $conditions['activated'] = true;
        }

        return array_merge($request->only($this->username(), 'password'), $conditions);
    }

    /**
     * Get the guard to be used during authentication.
     */
    private function guard(): Guard|StatefulGuard
    {
        return $this->authFactory->guard($this->guard);
    }
}
