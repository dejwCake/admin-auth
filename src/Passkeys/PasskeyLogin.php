<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Brackets\AdminAuth\Services\AdminLoginConditions;
use Brackets\AdminAuth\Services\PasskeyService;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

final readonly class PasskeyLogin
{
    public const int MAX_ATTEMPTS = 5;

    public const int DECAY_SECONDS = 60;

    public function __construct(
        private Config $config,
        private AuthFactory $authFactory,
        private PasskeyService $passkeyService,
        private AdminLoginConditions $adminLoginConditions,
        private RateLimiter $rateLimiter,
    ) {
    }

    /**
     * The admin the response signs in; nobody is logged in yet.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when throttled (429), or the passkey or its admin is rejected
     */
    public function authenticate(Session $session, array $credential, string $ip): Authenticatable
    {
        $throttleKey = sprintf('admin-auth:passkey-login:%s', $ip);
        if ($this->rateLimiter->tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = $this->rateLimiter->availableIn($throttleKey);

            throw ValidationException::withMessages([
                'passkey' => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $user = $this->userOf($this->passkeyService->authenticate($session, $credential)->admin_user_id);
        } catch (ValidationException $validationException) {
            $this->rateLimiter->hit($throttleKey, self::DECAY_SECONDS);

            throw $validationException;
        }

        $this->rateLimiter->clear($throttleKey);

        return $user;
    }

    /**
     * Through the guard's provider, so a project's own admin model works and a soft-deleted admin is not found.
     *
     * @throws ValidationException for a missing, forbidden or not activated admin
     */
    private function userOf(int $adminId): Authenticatable
    {
        $guard = $this->authFactory->guard($this->config->get('admin-auth.defaults.guard', 'admin'));
        $user = method_exists($guard, 'getProvider') ? $guard->getProvider()->retrieveById($adminId) : null;
        if (!$user instanceof Authenticatable) {
            throw ValidationException::withMessages([
                'passkey' => [trans('brackets/admin-auth::admin.passkeys.invalid')],
            ]);
        }

        if (!$this->adminLoginConditions->allows($user)) {
            throw ValidationException::withMessages(['passkey' => [trans('auth.failed')]]);
        }

        return $user;
    }
}
