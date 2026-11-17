<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The code or passkey asked for after the password login: throttled per admin, verifies the session on success.
 */
final readonly class TwoFactorChallenge
{
    public const int MAX_ATTEMPTS = 5;

    public const int DECAY_SECONDS = 60;

    public function __construct(
        private TwoFactorService $twoFactorService,
        private TwoFactorSession $twoFactorSession,
        private RateLimiter $rateLimiter,
        private PasskeyService $passkeyService,
    ) {
    }

    /**
     * Whether the admin has a second factor and this session did not pass it yet.
     */
    public function isPending(Session $session, Authenticatable $user): bool
    {
        return !$this->twoFactorSession->isVerified($session, $user) && $this->twoFactorService->hasSecondFactor($user);
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
        $this->ensureNotThrottled($throttleKey, $field);

        $valid = $recoveryCode !== ''
            ? $this->twoFactorService->verifyRecoveryCode($user, $recoveryCode)
            : $this->twoFactorService->verifyCode($user, $code);

        if (!$valid) {
            $this->rateLimiter->hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                $field => [trans(sprintf('brackets/admin-auth::admin.two_factor.invalid_%s', $field))],
            ]);
        }

        $this->pass($session, $user, $throttleKey);
    }

    /**
     * Which factors the challenge offers the admin.
     *
     * @return array{totp: bool, passkeys: bool}
     */
    public function factors(Authenticatable $user): array
    {
        return [
            'totp' => $this->twoFactorService->isEnabledFor($user),
            'passkeys' => $this->passkeyService->hasPasskeys($user),
        ];
    }

    /**
     * Options limited to the admin's own passkeys.
     *
     * @return array<string, array|string|int|float|bool|null>
     * @throws ValidationException when the admin can't use passkeys
     */
    public function passkeyOptions(Session $session, Authenticatable $user): array
    {
        return $this->passkeyService->challengeOptions($session, $this->passkeyUser($user));
    }

    /**
     * Verify a response signed by one of the admin's passkeys and mark the session as verified.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when throttled (429) or the passkey is rejected
     */
    public function verifyPasskey(Session $session, Authenticatable $user, array $credential): void
    {
        $throttleKey = $this->twoFactorService->throttleKey($user);
        $this->ensureNotThrottled($throttleKey, 'passkey');

        try {
            $this->passkeyService->verifyFor($session, $this->passkeyUser($user), $credential);
        } catch (ValidationException $validationException) {
            $this->rateLimiter->hit($throttleKey, self::DECAY_SECONDS);

            throw $validationException;
        }

        $this->pass($session, $user, $throttleKey);
    }

    /**
     * @throws ValidationException when the model has no passkeys or the table is missing
     */
    private function passkeyUser(Authenticatable $user): PasskeyAuthenticatable
    {
        if (!$user instanceof PasskeyAuthenticatable || !$this->passkeyService->isAvailable()) {
            throw ValidationException::withMessages([
                'passkey' => [trans('brackets/admin-auth::admin.passkeys.migration_missing')],
            ]);
        }

        return $user;
    }

    /**
     * @throws ValidationException with 429 once the admin failed too often, with codes or passkeys
     */
    private function ensureNotThrottled(string $throttleKey, string $field): void
    {
        if (!$this->rateLimiter->tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            return;
        }

        $seconds = $this->rateLimiter->availableIn($throttleKey);

        throw ValidationException::withMessages([
            $field => [trans('auth.throttle', ['seconds' => $seconds, 'minutes' => ceil($seconds / 60)])],
        ])->status(Response::HTTP_TOO_MANY_REQUESTS);
    }

    private function pass(Session $session, Authenticatable $user, string $throttleKey): void
    {
        $this->rateLimiter->clear($throttleKey);
        $session->regenerate();
        $this->twoFactorSession->markVerified($session, $user);
    }
}
