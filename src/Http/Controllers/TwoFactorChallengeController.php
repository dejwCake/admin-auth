<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Brackets\AdminAuth\Http\Requests\PasskeyCredentialRequest;
use Brackets\AdminAuth\Http\Requests\TwoFactorChallengeRequest;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorChallenge;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TwoFactorChallengeController extends Controller
{
    private readonly string $guard;

    private readonly string $redirectTo;

    public function __construct(
        private readonly Config $config,
        private readonly TwoFactorChallenge $twoFactorChallenge,
        private readonly Redirector $redirector,
        private readonly UrlGenerator $urlGenerator,
        private readonly ViewFactory $viewFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $this->redirectTo = $this->config->get('admin-auth.login_redirect', '/admin');
    }

    /**
     * Show the code form to an enrolled admin whose session is not verified yet.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $this->getUser($request);

        if (!$this->twoFactorChallenge->isPending($request->session(), $user)) {
            return $this->redirector->intended($this->redirectTo);
        }

        $factors = $this->twoFactorChallenge->factors($user);

        return $this->viewFactory->make('brackets/admin-auth::admin.two-factor.challenge', [
            'action' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/verify'),
            'redirectUrl' => $this->redirectTo,
            'hasTotp' => $factors['totp'],
            'hasPasskeys' => $factors['passkeys'],
            'passkeyOptionsUrl' => $this->urlGenerator->route(
                'brackets/admin-auth::admin/two-factor/challenge/passkey/options',
            ),
            'passkeyUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/challenge/passkey'),
        ]);
    }

    /**
     * Verify a TOTP or recovery code and send the admin to the page they first requested.
     *
     * @return array{redirect: string}|RedirectResponse
     */
    public function verify(TwoFactorChallengeRequest $request): array|RedirectResponse
    {
        $user = $this->getUser($request);
        $this->twoFactorChallenge->verify(
            $request->session(),
            $user,
            (string) $request->validated('code'),
            (string) $request->validated('recovery_code'),
        );

        if ($request->expectsJson()) {
            return ['redirect' => $request->session()->pull('url.intended', $this->redirectTo)];
        }

        return $this->redirector->intended($this->redirectTo);
    }

    /**
     * Options limited to the admin's own passkeys.
     *
     * @return array<string, array|string|int|float|bool|null>
     */
    public function passkeyOptions(Request $request): array
    {
        return $this->twoFactorChallenge->passkeyOptions($request->session(), $this->getUser($request));
    }

    /**
     * Verify a response of one of the admin's passkeys and return the page they first requested.
     *
     * @return array{redirect: string}
     */
    public function passkey(PasskeyCredentialRequest $request): array
    {
        $this->twoFactorChallenge->verifyPasskey($request->session(), $this->getUser($request), $request->credential());

        return ['redirect' => $request->session()->pull('url.intended', $this->redirectTo)];
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getUser(Request $request): Model&TwoFactorAuthenticatable
    {
        $user = $request->user($this->guard);
        if (!$user instanceof Model || !$user instanceof TwoFactorAuthenticatable) {
            throw NotFoundHttpException::fromStatusCode(404, trans('Admin User not found'));
        }

        return $user;
    }
}
