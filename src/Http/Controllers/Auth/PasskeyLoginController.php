<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers\Auth;

use Brackets\AdminAuth\Http\Controllers\Controller;
use Brackets\AdminAuth\Http\Requests\PasskeyCredentialRequest;
use Brackets\AdminAuth\Passkeys\PasskeyLogin;
use Brackets\AdminAuth\Services\LastLoginRecorder;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Passwordless login with a discoverable passkey. User verification on the device makes it two factors, so
 * the session counts as two-factor verified.
 */
final class PasskeyLoginController extends Controller
{
    private readonly string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly AuthFactory $authFactory,
        private readonly PasskeyService $passkeyService,
        private readonly PasskeyLogin $passkeyLogin,
        private readonly LastLoginRecorder $lastLoginRecorder,
        private readonly TwoFactorSession $twoFactorSession,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    public function options(Request $request): array
    {
        if (!$this->passkeyService->isAvailable()) {
            throw NotFoundHttpException::fromStatusCode(
                404,
                trans('brackets/admin-auth::admin.passkeys.migration_missing'),
            );
        }

        return $this->passkeyService->loginOptions($request->session());
    }

    public function login(PasskeyCredentialRequest $request): JsonResponse
    {
        $user = $this->passkeyLogin->authenticate($request->session(), $request->credential(), (string) $request->ip());

        $guard = $this->authFactory->guard($this->guard);
        assert($guard instanceof StatefulGuard);
        $guard->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // The Login event may have handed an earlier verification over; start from this admin's own.
        $this->twoFactorSession->forget($request->session());
        $this->twoFactorSession->markVerified($request->session(), $user);
        $this->lastLoginRecorder->record($user);

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
