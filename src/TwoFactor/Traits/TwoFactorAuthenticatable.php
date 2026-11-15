<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Traits;

use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\DecryptException;
use Psr\Log\LoggerInterface;

/**
 * Two-factor columns of the admin users table. They are never fillable — write them with forceFill().
 */
trait TwoFactorAuthenticatable
{
    /**
     * Cast and hide the two-factor columns.
     */
    public function initializeTwoFactorAuthenticatable(): void
    {
        $this->mergeCasts([
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'array',
            'two_factor_confirmed_at' => 'datetime',
        ]);

        $this->makeHidden(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
    }

    public function hasTwoFactorColumns(): bool
    {
        // Read from the loaded attributes: no schema query, and no MissingAttributeException without the migration.
        return array_key_exists('two_factor_confirmed_at', $this->getAttributes());
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->hasTwoFactorColumns()
            && $this->getAttributes()['two_factor_confirmed_at'] !== null
            && $this->getTwoFactorSecret() !== null;
    }

    public function hasTwoFactorData(): bool
    {
        $attributes = $this->getAttributes();

        return ($attributes['two_factor_secret'] ?? null) !== null
            || ($attributes['two_factor_recovery_codes'] ?? null) !== null
            || ($attributes['two_factor_confirmed_at'] ?? null) !== null;
    }

    public function getTwoFactorSecret(): ?string
    {
        if (($this->getAttributes()['two_factor_secret'] ?? null) === null) {
            return null;
        }

        try {
            return $this->getAttribute('two_factor_secret');
        } catch (DecryptException $decryptException) {
            // E.g. APP_KEY rotated without APP_PREVIOUS_KEYS: treat as no two-factor rather than a 500.
            Container::getInstance()->make(LoggerInterface::class)->error(
                'Two-factor secret of an admin user cannot be decrypted.',
                ['id' => $this->getKey(), 'exception' => $decryptException],
            );

            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    public function getTwoFactorRecoveryCodes(): array
    {
        if (($this->getAttributes()['two_factor_recovery_codes'] ?? null) === null) {
            return [];
        }

        return array_values($this->getAttribute('two_factor_recovery_codes') ?? []);
    }
}
