<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TwoFactorAuthenticatable extends Authenticatable
{
    /**
     * Whether the model was loaded with the two-factor columns (the migration has run).
     */
    public function hasTwoFactorColumns(): bool;

    /**
     * Whether two-factor authentication is confirmed and its secret is readable.
     */
    public function hasTwoFactorEnabled(): bool;

    /**
     * Whether any two-factor data is stored, confirmed or not.
     */
    public function hasTwoFactorData(): bool;

    /**
     * The decrypted two-factor secret, or null when there is none or it cannot be decrypted.
     */
    public function getTwoFactorSecret(): ?string;

    /**
     * Hashes of the unused recovery codes.
     *
     * @return array<int, string>
     */
    public function getTwoFactorRecoveryCodes(): array;
}
