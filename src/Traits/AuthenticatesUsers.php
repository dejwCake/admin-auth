<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

trait AuthenticatesUsers
{
    use RedirectsUsers;
    use ThrottlesLogins;

    /**
     * Show the application's login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (
            in_array(ThrottlesLogins::class, class_uses_recursive(static::class), true)
            && $this->hasTooManyLoginAttempts($request)
        ) {
            $this->fireLockoutEvent($request);

            $this->throwLockoutResponse($request);
        }

        if (!$this->attemptLogin($request)) {
            // If the login attempt was unsuccessful we will increment the number of attempts
            // to login and redirect the user back to the login form. Of course, when this
            // user surpasses their maximum number of attempts they will get locked out.
            if (in_array(ThrottlesLogins::class, class_uses_recursive(static::class), true)) {
                $this->incrementLoginAttempts($request);
            }

            $this->throwFailedLogin();
        }

        return $this->sendLoginResponse($request);
    }

    /**
     * Get the login username to be used by the controller.
     */
    public function username(): string
    {
        return 'email';
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $this->loggedOut($request);

        return $request->wantsJson()
            ? new JsonResponse(null, 204)
            : redirect('/');
    }

    /**
     * Validate the user login request.
     *
     * @throws ValidationException
     */
    protected function validateLogin(Request $request): void
    {
        $request->validate([
            $this->username() => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
    }

    /**
     * Attempt to log the user into the application.
     */
    protected function attemptLogin(Request $request): bool
    {
        return $this->guard()->attempt(
            $this->credentials($request),
            $request->filled('remember'),
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array<string, string>
     */
    protected function credentials(Request $request): array
    {
        return $request->only($this->username(), 'password');
    }

    /**
     * Send the response after the user was authenticated.
     */
    protected function sendLoginResponse(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        $this->authenticated($this->guard()->user());

        return $request->wantsJson()
            ? new JsonResponse(null, Response::HTTP_NO_CONTENT)
            : redirect()->intended($this->redirectPath());
    }

    /**
     * The user has been authenticated.
     */
    protected function authenticated(?Authenticatable $user): void
    {
        if ($user instanceof Model && Schema::hasColumn($user->getTable(), 'last_login_at')) {
            $user->last_login_at = now();
            $user->save();
        }
    }

    /**
     * Get the failed login response instance.
     *
     * @throws ValidationException
     */
    protected function throwFailedLogin(): void
    {
        throw ValidationException::withMessages([
            $this->username() => [trans('auth.failed')],
        ]);
    }

    /**
     * The user has logged out of the application.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function loggedOut(Request $request): void
    {
        //do nothing
    }

    /**
     * Get the guard to be used during authentication.
     */
    protected function guard(): Guard|StatefulGuard
    {
        return Auth::guard();
    }
}
