<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Facades\Activation;
use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\RedirectsUsers;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ActivationController extends Controller
{
    use RedirectsUsers;

    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    /**
     * Where to redirect users after activating their accounts.
     */
    protected string $redirectTo = '/';

    /**
     * Activation broker used for admin user
     */
    protected string $activationBroker = 'admin_users';

    public function __construct()
    {
        $this->guard = config('admin-auth.defaults.guard');
        $this->activationBroker = config('admin-auth.defaults.activations');
        $this->redirectTo = config('admin-auth.activation_redirect');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Activate user from token
     *
     * @throws ValidationException
     */
    public function activate(Request $request, string $token): RedirectResponse|View
    {
        if (!config('admin-auth.activation_enabled')) {
            return $this->sendActivationFailedResponse($request, Activation::ACTIVATION_DISABLED);
        }

        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Here we will attempt to activate the user's account. If it is successful we
        // will update the activation flag on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->broker()->activate(
            $this->credentials($request, $token),
            function ($user): void {
                $this->activateUser($user);
            },
        );

        // If the activation was successful, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response === Activation::ACTIVATED
            ? $this->sendActivationResponse($request, $response)
            : $this->sendActivationFailedResponse($request, $response);
    }

    /**
     * Get the broker to be used during activation.
     */
    public function broker(): ActivationBrokerContract
    {
        return Activation::broker($this->activationBroker);
    }

    /**
     * Get the activation validation rules.
     */
    protected function rules(): array
    {
        return [];
    }

    /**
     * Get the activation validation error messages.
     */
    protected function validationErrorMessages(): array
    {
        return [];
    }

    /**
     * Get the activation credentials from the request.
     *
     * @return array<string, string>
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function credentials(Request $request, string $token): array
    {
        return ['token' => $token];
    }

    /**
     * Activate the given user account.
     */
    protected function activateUser(CanActivateContract $user): void
    {
        $user->forceFill([
            'activated' => true,
        ])->save();
    }

    /**
     * Get the response for a successful activation.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function sendActivationResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);
        if ($response === Activation::ACTIVATED) {
            $message = trans('brackets/admin-auth::admin.activations.activated');
        }

        return redirect($this->redirectPath())
            ->with('status', $message);
    }

    /**
     * Get the response for a failed activation.
     */
    protected function sendActivationFailedResponse(Request $request, string $response): RedirectResponse|View
    {
        $message = trans($response);
        if ($response === Activation::INVALID_USER || $response === Activation::INVALID_TOKEN) {
            $message = trans('brackets/admin-auth::admin.activations.invalid_request');
        } else {
            if (Activation::ACTIVATION_DISABLED) {
                $message = trans('brackets/admin-auth::admin.activations.disabled');
            }
        }
        if (config('admin-auth.self_activation_form_enabled')) {
            return redirect(route('brackets/admin-auth::admin/activation'))
                ->withInput($request->only('email'))
                ->withErrors(['token' => $message]);
        } else {
            return view('brackets/admin-auth::admin.auth.activation.error')->withErrors(
                ['token' => $message],
            );
        }
    }
}
