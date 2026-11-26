<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Contracts;

use Brackets\AdminAuth\Models\AdminTwoFactor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

interface TwoFactorAuthenticatable extends Authenticatable
{
    /**
     * The admin's authenticator app (TOTP) record.
     *
     * @return HasOne<AdminTwoFactor, covariant Model>
     */
    public function twoFactor(): HasOne;

    /**
     * Whether the two-factor table exists (the migration has run).
     */
    public function hasTwoFactorTable(): bool;

    /**
     * The stored record, or null when there is none or the table does not exist yet.
     */
    public function getTwoFactorRecord(): ?AdminTwoFactor;

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
