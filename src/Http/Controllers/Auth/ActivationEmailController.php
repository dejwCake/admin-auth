<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Activation\Brokers\ActivationBrokerManager;
use Brackets\AdminAuth\Activation\Contracts\ActivationBroker as ActivationBrokerContract;
use Brackets\AdminAuth\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ActivationEmailController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Activation Email Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling activation emails and
    | assists in sending these notifications from
    | your application to your users.
    |
    */

    /**
     * Guard used for admin user
     */
    protected string $guard = 'admin';

    /**
     * Activation broker used for admin user
     */
    protected string $activationBroker = 'admin_users';

    public function __construct(public readonly ActivationBrokerManager $activationBrokerManager)
    {
        $this->guard = config('admin-auth.defaults.guard');
        $this->activationBroker = config('admin-auth.defaults.activations');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Display the form to request a activation link.
     *
     * @throws NotFoundHttpException
     */
    public function showLinkRequestForm(): View
    {
        if (config('admin-auth.self_activation_form_enabled')) {
            return view('brackets/admin-auth::admin.auth.activation.email');
        } else {
            abort(404);
        }
    }

    /**
     * Send an activation link to the given user.
     *
     * @throws ValidationException
     * @throws NotFoundHttpException
     */
    public function sendActivationEmail(Request $request): RedirectResponse
    {
        if (config('admin-auth.self_activation_form_enabled')) {
            if (!config('admin-auth.activation_enabled')) {
                return $this->sendActivationLinkFailedResponse($request, ActivationBrokerContract::ACTIVATION_DISABLED);
            }

            $this->validateEmail($request);

            // We will send the activation link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $response = $this->activationBrokerManager->broker($this->activationBroker)
                ->sendActivationLink($this->credentials($request));

            return $this->sendActivationLinkResponse($request, $response);
        } else {
            abort(404);
        }
    }

    /**
     * Validate the email for the given request.
     *
     * @throws ValidationException
     */
    protected function validateEmail(Request $request): void
    {
        $this->validate($request, ['email' => ['required', 'email']]);
    }

    /**
     * Get the response for a successful activation link.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function sendActivationLinkResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans('brackets/admin-auth::admin.activations.sent');

        return back()->with('status', $message);
    }

    /**
     * Get the response for a failed activation link.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    protected function sendActivationLinkFailedResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);
        if ($response === ActivationBrokerContract::ACTIVATION_DISABLED) {
            $message = trans('brackets/admin-auth::admin.activations.disabled');
        }

        return back()->withErrors(
            ['email' => $message],
        );
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array<string, string|bool>
     */
    protected function credentials(Request $request): array
    {
        $conditions = ['activated' => false];

        return array_merge($request->only('email'), $conditions);
    }
}
