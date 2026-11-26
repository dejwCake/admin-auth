<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Middleware;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds an admin with a second factor (TOTP or a passkey) on the two-factor challenge until the session is
 * verified, and a forced admin without one on the settings page until they enrol.
 */
final readonly class EnsureTwoFactorAuthenticated
{
    public const string SETTINGS_ROUTE = 'brackets/admin-auth::admin/two-factor';
    public const string CHALLENGE_ROUTE = 'brackets/admin-auth::admin/two-factor/challenge';

    /**
     * Routes an enrolled admin can reach before passing the challenge.
     */
    private const array CHALLENGE_ROUTES = [
        self::CHALLENGE_ROUTE,
        'brackets/admin-auth::admin/two-factor/verify',
        'brackets/admin-auth::admin/two-factor/challenge/passkey/options',
        'brackets/admin-auth::admin/two-factor/challenge/passkey',
    ];

    /**
     * Routes a forced admin without two-factor can reach before enrolling.
     */
    private const array ENROLMENT_ROUTES = [
        self::SETTINGS_ROUTE,
        'brackets/admin-auth::admin/two-factor/enable',
        'brackets/admin-auth::admin/two-factor/confirm',
        'brackets/admin-auth::admin/passkeys',
        'brackets/admin-auth::admin/passkeys/options',
        'brackets/admin-auth::admin/passkeys/store',
    ];

    private string $guard;

    public function __construct(
        private Config $config,
        private AuthFactory $authFactory,
        private TwoFactorService $twoFactorService,
        private TwoFactorSession $twoFactorSession,
        private Redirector $redirector,
        private UrlGenerator $urlGenerator,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $this->authFactory->guard($this->guard)->user();
        if ($user === null || !$request->hasSession()) {
            return $next($request);
        }

        $session = $request->session();
        // Verified (also by a verified admin who impersonates this one), or known to have nothing to verify:
        // no query on every admin request.
        if (
            $this->twoFactorSession->isVerified($session, $user)
            || $this->twoFactorSession->isUnenrolled($session, $user)
        ) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if ($this->twoFactorService->hasSecondFactor($user)) {
            return in_array($routeName, self::CHALLENGE_ROUTES, true)
                ? $next($request)
                : $this->deny($request, self::CHALLENGE_ROUTE);
        }

        if ($this->twoFactorService->isRequiredFor($user)) {
            return in_array($routeName, self::ENROLMENT_ROUTES, true)
                ? $next($request)
                : $this->deny($request, self::SETTINGS_ROUTE);
        }

        $this->twoFactorSession->markUnenrolled($session, $user);

        return $next($request);
    }

    private function deny(Request $request, string $routeName): JsonResponse|RedirectResponse
    {
        $url = $this->urlGenerator->route($routeName);

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => trans($routeName === self::CHALLENGE_ROUTE
                    ? 'brackets/admin-auth::admin.two_factor.challenge_required'
                    : 'brackets/admin-auth::admin.two_factor.enrolment_required'),
                'redirect' => $url,
            ], Response::HTTP_FORBIDDEN);
        }

        // Only a page the admin can open again is kept as the intended URL.
        return $request->isMethod('GET')
            ? $this->redirector->guest($url)
            : $this->redirector->to($url);
    }
}
