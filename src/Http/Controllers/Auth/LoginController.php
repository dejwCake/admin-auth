<?php

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\AuthenticatesUsers;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
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
    protected string $redirectTo = '/admin';

    /**
     * Where to redirect users after logout.
     */
    protected string $redirectToAfterLogout = '/admin/login';

    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    public function __construct()
    {
        $this->guard = config('admin-auth.defaults.guard');
        $this->redirectTo = config('admin-auth.login_redirect');
        $this->redirectToAfterLogout = config('admin-auth.logout_redirect');
        $this->middleware('guest.admin:' . $this->guard)->except('logout');
    }

    /**
     * Show the application's login form.
     */
    public function showLoginForm(): View
    {
        return view('brackets/admin-auth::admin.auth.login');
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->guard()->logout();

        $request->session()->flush();

        $request->session()->regenerate();

        return redirect($this->redirectToAfterLogout);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array<string, string|bool>
     */
    protected function credentials(Request $request): array
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
     * Get the post register / login redirect path.
     */
    public function redirectAfterLogoutPath(): string
    {
        if (method_exists($this, 'redirectToAfterLogout')) {
            return $this->redirectToAfterLogout();
        }

        return property_exists($this, 'redirectToAfterLogout') ? $this->redirectToAfterLogout : '/';
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard(): Guard|StatefulGuard
    {
        return Auth::guard($this->guard);
    }
}
