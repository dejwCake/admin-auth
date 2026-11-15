<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The code asked for after the password login: throttled per admin, verifies the session on success.
 */
final readonly class TwoFactorChallenge
{
    public const int MAX_ATTEMPTS = 5;

    public const int DECAY_SECONDS = 60;

    public function __construct(
        private TwoFactorService $twoFactorService,
        private TwoFactorSession $twoFactorSession,
        private RateLimiter $rateLimiter,
    ) {
    }

    /**
     * Whether the admin has two-factor and this session did not pass it yet.
     */
    public function isPending(Session $session, Authenticatable $user): bool
    {
        return $this->twoFactorService->isEnabledFor($user) && !$this->twoFactorSession->isVerified($session, $user);
    }

    /**
     * Verify a TOTP code, or a recovery code when one is given, and mark the session as verified.
     *
     * @throws ValidationException when throttled (429) or the code is wrong
     */
    public function verify(
        Session $session,
        Model&TwoFactorAuthenticatable $user,
        string $code,
        string $recoveryCode,
    ): void {
        $field = $recoveryCode !== '' ? 'recovery_code' : 'code';
        $throttleKey = $this->twoFactorService->throttleKey($user);

        if ($this->rateLimiter->tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = $this->rateLimiter->availableIn($throttleKey);

            throw ValidationException::withMessages([
                $field => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])],
            ])->status(Response::HTTP_TOO_MANY_REQUESTS);
        }

        $valid = $recoveryCode !== ''
            ? $this->twoFactorService->verifyRecoveryCode($user, $recoveryCode)
            : $this->twoFactorService->verifyCode($user, $code);

        if (!$valid) {
            $this->rateLimiter->hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                $field => [trans(sprintf('brackets/admin-auth::admin.two_factor.invalid_%s', $field))],
            ]);
        }

        $this->rateLimiter->clear($throttleKey);
        $session->regenerate();
        $this->twoFactorSession->markVerified($session, $user);
    }
}
