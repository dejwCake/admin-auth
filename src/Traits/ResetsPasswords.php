<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Traits;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

trait ResetsPasswords
{
    use RedirectsUsers;

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     */
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email],
        );
    }

    /**
     * Reset the given user's password.
     */
    public function reset(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Here we will attempt to reset the user's password. If it is successful we
        // will update the password on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->broker()->reset(
            $this->credentials($request),
            function (CanResetPassword&Authenticatable&Model $user, string $password): void {
                $this->resetPassword($user, $password);
            },
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response === Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response)
            : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): PasswordBroker
    {
        return Password::broker();
    }

    /**
     * Get the password reset validation rules.
     *
     * @return array<string, array<string>>
     */
    protected function rules(): array
    {
        return [
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    /**
     * Get the password reset validation error messages.
     */
    protected function validationErrorMessages(): array
    {
        return [];
    }

    /**
     * Get the password reset credentials from the request.
     *
     * @return array<string, string>
     */
    protected function credentials(Request $request): array
    {
        return $request->only('email', 'password', 'password_confirmation', 'token');
    }

    /**
     * Reset the given user's password.
     */
    protected function resetPassword(CanResetPassword&Authenticatable&Model $user, string $password): void
    {
        $this->setUserPassword($user, $password);

        $user->setRememberToken(Str::random(60));

        $user->save();

        event(new PasswordReset($user));

        $this->guard()->login($user);
    }

    /**
     * Set the user's password.
     */
    protected function setUserPassword(CanResetPassword&Model $user, string $password): void
    {
        if (!$user->hasAttribute('password')) {
            throw new RuntimeException('User must have a password property for password reset.');
        }
        $user->fill(['password' => Hash::make($password)]);
    }

    /**
     * Get the response for a successful password reset.
     */
    protected function sendResetResponse(Request $request, string $response): RedirectResponse|JsonResponse
    {
        return $request->wantsJson()
            ? new JsonResponse(['message' => trans($response)], 200)
            : redirect($this->redirectPath())->with('status', trans($response));
    }

    /**
     * Get the response for a failed password reset.
     *
     * @throws ValidationException
     */
    protected function sendResetFailedResponse(Request $request, string $response): RedirectResponse
    {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                'email' => [trans($response)],
            ]);
        }

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => trans($response)]);
    }

    /**
     * Get the guard to be used during password reset.
     */
    protected function guard(): Guard|StatefulGuard
    {
        return Auth::guard();
    }
}
