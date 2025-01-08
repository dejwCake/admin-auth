<?php

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Traits\SendsPasswordResetEmails;
use Illuminate\Contracts\Auth\PasswordBroker as PasswordBrokerContract;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
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
    protected string $guard = 'admin';

    /**
     * Password broker used for admin user
     */
    protected string $passwordBroker = 'admin_users';

    public function __construct()
    {
        $this->guard = config('admin-auth.defaults.guard');
        $this->passwordBroker = config('admin-auth.defaults.passwords');
        $this->middleware('guest.admin:' . $this->guard);
    }

    /**
     * Display the form to request a password reset link.
     */
    public function showLinkRequestForm(): View
    {
        return view('brackets/admin-auth::admin.auth.passwords.email');
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
            $request->only('email')
        );

        return $response === Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }

    /**
     * Get the response for a successful password reset link.
     */
    protected function sendResetLinkResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);
        if ($response === Password::RESET_LINK_SENT) {
            $message = trans('brackets/admin-auth::admin.passwords.sent');
        }
        return back()->with('status', $message);
    }

    /**
     * Get the response for a failed password reset link.
     */
    protected function sendResetLinkFailedResponse(Request $request, string $response): RedirectResponse
    {
        $message = trans($response);

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => $message]);
    }

    /**
     * Get the broker to be used during password reset.
     */
    public function broker(): PasswordBrokerContract
    {
        return Password::broker($this->passwordBroker);
    }
}
