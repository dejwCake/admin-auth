<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\QrCodeRenderer;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;

final readonly class TwoFactorService
{
    public const int RECOVERY_CODE_COUNT = 8;

    public const string FACTOR_TOTP = 'totp';
    public const string FACTOR_PASSKEY = 'passkey';

    /**
     * Codes are accepted one step (30 s) either side, so a replay is possible for at most 90 s.
     */
    private const int WINDOW = 1;

    private const int REPLAY_TTL_SECONDS = 150;

    public function __construct(
        private Google2FA $google2fa,
        private Cache $cache,
        private Hasher $hasher,
        private Config $config,
        private RateLimiter $rateLimiter,
        private QrCodeRenderer $qrCodeRenderer,
        private PasskeyService $passkeyService,
    ) {
    }

    /**
     * Whether the model supports two-factor and its table exists.
     */
    public function isAvailableFor(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable && $user instanceof Model && $user->hasTwoFactorTable();
    }

    public function isEnabledFor(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable && $user->hasTwoFactorEnabled();
    }

    /**
     * Whether the admin has a second factor: confirmed TOTP or at least one passkey.
     */
    public function hasSecondFactor(Authenticatable $user): bool
    {
        return $this->isEnabledFor($user) || $this->passkeyService->hasPasskeys($user);
    }

    /**
     * Whether the admin may remove one of their factors (TOTP, or one passkey): always, unless two-factor is
     * required for them and it is their last one.
     */
    public function canRemoveFactor(Authenticatable $user, string $factor): bool
    {
        if (!$this->isRequiredFor($user)) {
            return true;
        }

        $passkeys = $this->passkeyService->countFor($user);

        return $factor === self::FACTOR_TOTP
            ? $passkeys > 0
            : $passkeys > 1 || $this->isEnabledFor($user);
    }

    /**
     * Two-factor is required when `required` is on and `required_roles` is empty or holds one of the admin's roles.
     */
    public function isRequiredFor(Authenticatable $user): bool
    {
        if (!$this->config->get('admin-auth.two_factor.required', false)) {
            return false;
        }

        $roles = $this->config->get('admin-auth.two_factor.required_roles', []);
        if ($roles === [] || $roles === null) {
            return true;
        }

        return !method_exists($user, 'hasAnyRole') || $user->hasAnyRole($roles);
    }

    public function forgetUnconfirmedEnrolment(Authenticatable $user): void
    {
        if (!$user instanceof TwoFactorAuthenticatable || !$user instanceof Model) {
            return;
        }

        $record = $user->getTwoFactorRecord();
        if ($record === null || $record->confirmed_at !== null) {
            return;
        }

        $this->forget($user);
    }

    /**
     * Store a new, unconfirmed secret (replacing any unconfirmed one) and return it with its QR code.
     *
     * @return array{secret: string, qr_code: string}
     */
    public function startEnrolment(Model&TwoFactorAuthenticatable $user): array
    {
        $secret = $this->google2fa->generateSecretKey();

        $this->store($user, ['secret' => $secret, 'recovery_codes' => null, 'confirmed_at' => null]);

        return [
            'secret' => $secret,
            'qr_code' => $this->qrCodeRenderer->svg($this->otpauthUrl($user, $secret)),
        ];
    }

    /**
     * Confirm enrolment with a code for the new secret; returns the plain recovery codes.
     *
     * @return array<int, string>
     * @throws ValidationException for a wrong code
     */
    public function confirmEnrolment(Model&TwoFactorAuthenticatable $user, string $code): array
    {
        if (!$this->verifyCode($user, $code)) {
            throw ValidationException::withMessages([
                'code' => [trans('brackets/admin-auth::admin.two_factor.invalid_code')],
            ]);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $this->store($user, [
            'recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
            'confirmed_at' => CarbonImmutable::now(),
        ]);

        return $recoveryCodes;
    }

    /**
     * Verify a TOTP code; each code is accepted only once.
     */
    public function verifyCode(Model&TwoFactorAuthenticatable $user, string $code): bool
    {
        $secret = $user->getTwoFactorSecret();
        $code = preg_replace('/\s+/', '', $code) ?? '';
        if ($secret === null || $code === '') {
            return false;
        }

        $replayKey = $this->replayKey($user);
        $timestep = $this->google2fa->verifyKeyNewer(
            $secret,
            $code,
            (int) $this->cache->get($replayKey, 0),
            self::WINDOW,
        );
        if ($timestep === false) {
            return false;
        }
        if (!$this->cache->add(sprintf('%s:%d', $replayKey, $timestep), true, self::REPLAY_TTL_SECONDS)) {
            return false;
        }

        $this->cache->put($replayKey, $timestep, self::REPLAY_TTL_SECONDS);

        return true;
    }

    /**
     * Verify a recovery code and consume it.
     */
    public function verifyRecoveryCode(Model&TwoFactorAuthenticatable $user, string $code): bool
    {
        $code = Str::lower(trim($code));
        if ($code === '') {
            return false;
        }

        $record = $user->getTwoFactorRecord();
        if ($record === null) {
            return false;
        }

        $consumed = $record->consumeRecoveryCode(fn (string $hash): bool => $this->hasher->check($code, $hash));
        if ($consumed === null) {
            return false;
        }
        $user->setRelation('twoFactor', $consumed);

        return true;
    }

    /**
     * Replace every recovery code; returns the new plain codes.
     *
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(Model&TwoFactorAuthenticatable $user): array
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $this->store($user, ['recovery_codes' => $this->hashRecoveryCodes($recoveryCodes)]);

        return $recoveryCodes;
    }

    /**
     * Remove the secret, recovery codes and confirmation, and the admin's replay and throttle state.
     */
    public function disable(Model&TwoFactorAuthenticatable $user): void
    {
        $this->forget($user);

        $this->cache->forget($this->replayKey($user));
        $this->rateLimiter->clear($this->throttleKey($user));
    }

    public function throttleKey(Authenticatable $user): string
    {
        return sprintf('admin-auth:two-factor:%s', $user->getAuthIdentifier());
    }

    /* ************************ HELPERS ************************ */

    /**
     * @param array<string, string|array<int, string>|CarbonImmutable|null> $attributes
     */
    private function store(Model&TwoFactorAuthenticatable $user, array $attributes): void
    {
        $record = $user->getTwoFactorRecord() ?? $user->twoFactor()->make();
        $record->forceFill($attributes)->save();
        $user->setRelation('twoFactor', $record);
    }

    private function forget(Model&TwoFactorAuthenticatable $user): void
    {
        $user->twoFactor()->delete();
        $user->setRelation('twoFactor', null);
    }

    private function otpauthUrl(Model $user, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl(
            (string) ($this->config->get('admin-auth.two_factor.issuer') ?? $this->config->get('app.name')),
            (string) $user->getAttribute('email'),
            $secret,
        );
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < self::RECOVERY_CODE_COUNT; $i++) {
            $codes[] = sprintf('%s-%s', Str::lower(Str::random(5)), Str::lower(Str::random(5)));
        }

        return $codes;
    }

    /**
     * @param array<int, string> $codes
     * @return array<int, string>
     */
    private function hashRecoveryCodes(array $codes): array
    {
        return array_map(fn (string $code): string => $this->hasher->make($code), $codes);
    }

    private function replayKey(Authenticatable $user): string
    {
        return sprintf('admin-auth:two-factor:last:%s', $user->getAuthIdentifier());
    }
}
