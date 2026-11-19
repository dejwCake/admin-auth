<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

/**
 * A snapshot of an admin's second factors: whether they have TOTP data, and the names of their passkeys.
 */
final readonly class SecondFactors
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
