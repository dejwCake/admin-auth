<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorPresentation;
use Illuminate\Contracts\Auth\Authenticatable;
use Override;

/**
 * A further factor (e.g. e-mail codes), always available and active.
 */
final readonly class TestSecondFactor implements SecondFactor
{
    #[Override]
    public function key(): string
    {
        return 'test';
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function isAvailableFor(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function isActiveFor(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function present(Authenticatable $user): SecondFactorPresentation
    {
        return new SecondFactorPresentation(
            key: 'test',
            name: 'E-mail codes',
            icon: 'fa fa-envelope',
            available: true,
            active: true,
            status: 'On',
        );
    }
}
