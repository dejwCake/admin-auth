<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Activation\Contracts\ActivationBroker;
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory;
use Brackets\AdminAuth\Http\Controllers\Controller;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ActivationEmailController extends Controller
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
    private string $guard;

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
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Display the form to request an activation link.
     *
     * @throws NotFoundHttpException
     */
    public function showLinkRequestForm(): View
    {
        if ($this->config->get('admin-auth.self_activation_form_enabled')) {
            return $this->viewFactory->make('brackets/admin-auth::admin.auth.activation.email');
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Send an activation link to the given user.
     *
     * @throws ValidationException
     * @throws NotFoundHttpException
     */
    public function sendActivationEmail(Request $request): RedirectResponse|JsonResponse
    {
        if ($this->config->get('admin-auth.self_activation_form_enabled')) {
            if (!$this->config->get('admin-auth.activation_enabled')) {
                return $this->sendActivationLinkFailedResponse($request, ActivationBroker::ACTIVATION_DISABLED);
            }

            $this->validateEmail($request);

            // We will send the activation link to this user. Once we have attempted
            // to send the link, we will examine the response then see the message we
            // need to show to the user. Finally, we'll send out a proper response.
            $response = $this->activationBrokerFactory
                ->broker($this->activationBroker)
                ->sendActivationLink($this->credentials($request));

            return $response === ActivationBroker::ACTIVATION_LINK_SENT
                ? $this->sendActivationLinkResponse($request, $response)
                : $this->sendActivationLinkFailedResponse($request, $response);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * Validate the email for the given request.
     *
     * @throws ValidationException
     */
    private function validateEmail(Request $request): void
    {
        $this->validate($request, ['email' => ['required', 'email']]);
    }

    /**
     * Get the response for a successful activation link.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function sendActivationLinkResponse(Request $request, string $response): RedirectResponse|JsonResponse
    {
        $message = trans('brackets/admin-auth::admin.activations.sent');

        if ($request->wantsJson()) {
            return new JsonResponse(['message' => $message], 200);
        }

        return $this->redirector->back()
            ->with('status', $message);
    }

    /**
     * Get the response for a failed activation link.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function sendActivationLinkFailedResponse(Request $request, string $response): RedirectResponse|JsonResponse
    {
        $message = trans($response);
        if ($response === ActivationBroker::ACTIVATION_DISABLED) {
            $message = trans('brackets/admin-auth::admin.activations.disabled');
        }
        if ($response === ActivationBroker::INVALID_USER) {
            $message = trans('brackets/admin-auth::admin.activations.invalid_user');
        }
        if ($response === ActivationBroker::INVALID_TOKEN) {
            $message = trans('brackets/admin-auth::admin.activations.invalid_token');
        }

        if ($request->wantsJson()) {
            throw ValidationException::withMessages(['email' => $message]);
        }

        return $this->redirector->back()
            ->withErrors(['email' => $message]);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @return array<string, string|bool>
     */
    private function credentials(Request $request): array
    {
        $conditions = ['activated' => false];

        return array_merge($request->only('email'), $conditions);
    }
}
