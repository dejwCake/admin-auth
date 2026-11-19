<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

/**
 * The second factors of an admin that a security reset removes (or removed).
 */
final readonly class SecurityResetResult
{
    /**
     * @param array<int, string> $passkeys names of the passkeys
     */
    public function __construct(public bool $totp, public array $passkeys)
    {
    }

    public function isEmpty(): bool
    {
        return !$this->totp && $this->passkeys === [];
    }
}
