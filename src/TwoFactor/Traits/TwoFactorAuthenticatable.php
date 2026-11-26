<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Traits;

use Brackets\AdminAuth\Models\AdminTwoFactor;
use Brackets\AdminAuth\Services\SchemaCheck;
use Illuminate\Container\Container;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Psr\Log\LoggerInterface;

/**
 * The admin's authenticator app (TOTP), stored in admin_two_factors. The record is loaded once per model and
 * written only by TwoFactorService.
 */
trait TwoFactorAuthenticatable
{
    /**
     * The record is loaded by every two-factor check; it never belongs to the admin's serialised form.
     */
    public function initializeTwoFactorAuthenticatable(): void
    {
        $this->makeHidden('twoFactor');
    }

    /**
     * @return HasOne<AdminTwoFactor, $this>
     */
    public function twoFactor(): HasOne
    {
        return $this->hasOne(AdminTwoFactor::class, 'admin_user_id');
    }

    public function hasTwoFactorTable(): bool
    {
        return Container::getInstance()->make(SchemaCheck::class)->hasTable(new AdminTwoFactor());
    }

    public function getTwoFactorRecord(): ?AdminTwoFactor
    {
        if (!$this->hasTwoFactorTable()) {
            return null;
        }

        $record = $this->getRelationValue('twoFactor');

        return $record instanceof AdminTwoFactor ? $record : null;
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->getTwoFactorRecord()?->confirmed_at !== null && $this->getTwoFactorSecret() !== null;
    }

    public function hasTwoFactorData(): bool
    {
        return $this->getTwoFactorRecord() !== null;
    }

    public function getTwoFactorSecret(): ?string
    {
        $record = $this->getTwoFactorRecord();
        if ($record === null) {
            return null;
        }

        try {
            return $record->getAttribute('secret');
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
        return array_values($this->getTwoFactorRecord()->recovery_codes ?? []);
    }
}
