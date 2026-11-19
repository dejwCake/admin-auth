<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

/**
 * Where a second factor is managed when it has no component on the settings page, e.g. the passkeys page.
 */
final readonly class SecondFactorLink
{
    public function __construct(public string $url, public string $label)
    {
    }
}
