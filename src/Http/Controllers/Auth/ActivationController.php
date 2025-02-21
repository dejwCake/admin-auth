<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory;
use Brackets\AdminAuth\Activation\Contracts\CanActivate;
use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\RedirectsUsers;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;

final class ActivationController extends Controller
{
    use RedirectsUsers;

    /**
     * Guard used for admin user
     */
    private string $guard;

    /**
     * Where to redirect users after activating their accounts.
     */
    private string $redirectTo;

    /**
     * Activation broker used for admin user
     */
    private string $activationBroker;

    public function __construct(
        private readonly ActivationBrokerFactory $activationBrokerFactory,
        private readonly Config $config,
        private readonly ViewFactory $viewFactory,
        private readonly Redirector $redirector,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $this->activationBroker = $this->config->get('admin-auth.defaults.activations', 'admin_users');
        $this->redirectTo = $this->config->get('admin-auth.activation_redirect', '/');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Activate user from token
     *
     * @throws ValidationException
     */
    public function activate(Request $request, string $token): RedirectResponse|View
    {
        if (!$this->config->get('admin-auth.activation_enabled')) {
            return $this->sendActivationFailedResponse($request, ActivationBroker::ACTIVATION_DISABLED);
        }

        $this->validate($request, $this->rules(), $this->validationErrorMessages());

        // Here we will attempt to activate the user's account. If it is successful we
        // will update the activation flag on an actual user model and persist it to the
        // database. Otherwise, we will parse the error and return the response.
        $response = $this->activationBrokerFactory
            ->broker($this->activationBroker)
            ->activate(
                $this->credentials($request, $token),
                function ($user): void {
                    $this->activateUser($user);
                },
            );

        // If the activation was successful, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response === ActivationBroker::ACTIVATED
            ? $this->sendActivationResponse($request, $response)
            : $this->sendActivationFailedResponse($request, $response);
    }

    /**
     * Get the activation validation rules.
     */
    private function rules(): array
    {
        return [];
    }

    /**
     * Get the activation validation error messages.
     */
    private function validationErrorMessages(): array
    {
        return [];
    }

    /**
     * Get the activation credentials from the request.
     *
     * @return array<string, string>
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function credentials(Request $request, string $token): array
    {
        return ['token' => $token];
    }

    /**
     * Activate the given user account.
     */
    private function activateUser(CanActivate&Model $user): void
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
    private function sendActivationResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);
        if ($response === ActivationBroker::ACTIVATED) {
            $message = trans('brackets/admin-auth::admin.activations.activated');
        }

        return $this->redirector->to($this->redirectPath())
            ->with('status', $message);
    }

    /**
     * Get the response for a failed activation.
     */
    private function sendActivationFailedResponse(Request $request, string $response): RedirectResponse|View
    {
        $message = trans($response);
        if ($response === ActivationBroker::INVALID_USER || $response === ActivationBroker::INVALID_TOKEN) {
            $message = trans('brackets/admin-auth::admin.activations.invalid_request');
        } else {
            if ($response === ActivationBroker::ACTIVATION_DISABLED) {
                $message = trans('brackets/admin-auth::admin.activations.disabled');
            }
        }
        if ($this->config->get('admin-auth.self_activation_form_enabled')) {
            return $this->redirector->route('brackets/admin-auth::admin/activation')
                ->withInput($request->only('email'))
                ->withErrors(['token' => $message]);
        } else {
            return $this->viewFactory->make('brackets/admin-auth::admin.auth.activation.error')
                ->withErrors(['token' => $message]);
        }
    }
}
