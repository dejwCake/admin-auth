<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Removes every second factor of an admin (TOTP data and passkeys): the recovery path for a locked-out
 * admin, shared by `admin-auth:two-factor-reset` and the admin-users endpoint.
 */
final readonly class SecurityResetService
{
    public function __construct(private TwoFactorService $twoFactorService, private PasskeyService $passkeyService,)
    {
    }

    /**
     * The admin's second factors now, i.e. what a reset would remove; null when neither the two-factor
     * columns nor the passkeys table exist.
     */
    public function factorsOf(Authenticatable $user): ?SecondFactors
    {
        if (!$this->hasTotpColumns($user) && !$this->passkeyService->isAvailableFor($user)) {
            return null;
        }

        return new SecondFactors($this->hasTotpData($user), $this->passkeyNames($user));
    }

    /**
     * Remove the admin's second factors (also the TOTP replay and throttle state); returns the factors
     * as they were before, i.e. what was removed.
     */
    public function reset(Authenticatable $user): SecondFactors
    {
        $factors = $this->factorsOf($user) ?? new SecondFactors(false, []);

        if ($factors->totp && $user instanceof Model && $user instanceof TwoFactorAuthenticatable) {
            $this->twoFactorService->disable($user);
        }

        if ($factors->passkeys !== [] && $user instanceof PasskeyAuthenticatable) {
            $user->passkeys()->delete();
        }

        return $factors;
    }

    private function hasTotpColumns(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable && $this->twoFactorService->isAvailableFor($user);
    }

    private function hasTotpData(Authenticatable $user): bool
    {
        return $user instanceof TwoFactorAuthenticatable && $this->hasTotpColumns($user) && $user->hasTwoFactorData();
    }

    /**
     * @return array<int, string>
     */
    private function passkeyNames(Authenticatable $user): array
    {
        if (!$user instanceof PasskeyAuthenticatable || !$this->passkeyService->isAvailableFor($user)) {
            return [];
        }

        return $user->passkeys()->orderBy('id')->pluck('name')->all();
    }
}
