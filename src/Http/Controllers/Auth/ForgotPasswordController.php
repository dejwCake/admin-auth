<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\SendsPasswordResetEmails;
use Illuminate\Contracts\Auth\PasswordBroker;
use Illuminate\Contracts\Auth\PasswordBrokerFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

final class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */
    use SendsPasswordResetEmails;

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
        private readonly PasswordBrokerFactory $passwordBrokerFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $this->passwordBroker = $this->config->get('admin-auth.defaults.passwords', 'admin_users');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return $this->viewFactory->make('brackets/admin-auth::admin.auth.passwords.email');
    }

    /**
     * Send a reset link to the given user.
     */
    public function sendResetLinkEmail(Request $request): RedirectResponse
    {
        $this->validateEmail($request);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $response = $this->broker()->sendResetLink(
            $request->only('email'),
        );

        return $response === PasswordBroker::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Get the response for a failed password reset link.
     */
    protected function sendResetLinkFailedResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);

        return $this->redirector->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }

    /**
     * Get the broker to be used during password reset.
     */
    private function broker(): PasswordBroker
    {
        return $this->passwordBrokerFactory->broker($this->passwordBroker);
    }

    /**
     * Get the response for a successful password reset link.
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    private function sendResetLinkResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);
        if ($response === PasswordBroker::RESET_LINK_SENT) {
            $message = trans('brackets/admin-auth::admin.passwords.sent');
        }

        return $this->redirector->back()
            ->with('status', $message);
    }
}
