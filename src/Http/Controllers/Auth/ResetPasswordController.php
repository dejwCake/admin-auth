<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\ResetsPasswords;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Psr\Log\LoggerInterface;

final class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */
    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     */
    private string $redirectTo;

    /**
     * Guard used for admin user
     */
    private string $guard;

    /**
     * Password broker used for admin user
     */
    private string $passwordBroker;

    public function __construct(
        private readonly Config $config,
        private readonly ViewFactory $viewFactory,
        private readonly Redirector $redirector,
        private readonly AuthFactory $authFactory,
        private readonly PasswordBrokerFactory $passwordBrokerFactory,
        private readonly Hasher $hasher,
        private readonly UrlGenerator $urlGenerator,
        private readonly SessionManager $sessionManager,
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $this->passwordBroker = $this->config->get('admin-auth.defaults.passwords', 'admin_users');
        $this->redirectTo = $this->config->get('admin-auth.password_reset_redirect', '/');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     */
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return $this->viewFactory->make('brackets/admin-auth::admin.auth.passwords.reset', [
            'token' => $token,
            'email' => $request->email,
            'action' => $this->urlGenerator->route('brackets/admin-auth::admin/password/reset'),
            'redirectUrl' => $this->redirectTo,
            'loginUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/show-login-form'),
            'statusMessage' => $this->sessionManager->get('status', ''),
        ]);
    }

    /**
     * Reset the given user's password.
     *
     * @throws ValidationException
     */
    public function reset(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful, we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->broker()
            ->reset(
                $this->credentials($request),
                function (CanResetPassword&Authenticatable&Model $user, string $password): void {
                    $this->resetPassword($user, $password);
                },
            );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error, we can
        // redirect them back to where they came from with their error message.
        return $response === PasswordBroker::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Reset the given user's password.
     */
    private function resetPassword(CanResetPassword&Authenticatable&Model $user, string $password): void
    {
        $user->forceFill([
            'password' => $this->hasher->make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $this->dispatcher->dispatch(new PasswordReset($user));

        if ($this->loginCheck($user)) {
            $this->guard()
                ->login($user);
        }
    }

    /**
     * Get the response for a successful password reset.
     */
    private function sendResetResponse(Request $request, string $response): RedirectResponse|JsonResponse
    {
        $message = trans('brackets/admin-auth::admin.passwords.reset');

        if ($request->wantsJson()) {
            return new JsonResponse([
                'message' => $message,
                'redirect' => $this->redirectPath(),
            ], 200);
        }

        return $this->redirector->to($this->redirectPath())
            ->with('status', $message);
    }

    /**
     * Get the response for a failed password reset.
     *
     * @throws ValidationException
     */
    private function sendResetFailedResponse(Request $request, string $response): RedirectResponse
    {
        $this->logger->error('Password reset failed: ' . $response);
        $message = trans('brackets/admin-auth::admin.passwords.invalid_token');

        if ($request->wantsJson()) {
            throw ValidationException::withMessages(['email' => $message]);
        }

        return $this->redirector->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array<string, array<string>>
     */
    private function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email', 'string'],
            'password' => [
                'required',
                'confirmed',
                'min:8',
                'regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9]).*$/',
                'string',
            ],
        ];
    }

    /**
     * Check if provided user can be logged in
     */
    private function loginCheck(CanResetPassword $user): bool
    {
        return (!property_exists($user, 'activated') || $user->activated === null || $user->activated)
            && (!property_exists($user, 'forbidden') || $user->forbidden === null || !$user->forbidden);
    }

    /**
     * Get the guard to be used during password reset.
     */
    private function guard(): Guard|StatefulGuard
    {
        return $this->authFactory->guard($this->guard);
    }

    /**
     * Get the broker to be used during password reset.
     */
    private function broker(): PasswordBroker
    {
        return $this->passwordBrokerFactory->broker($this->passwordBroker);
    }
}
