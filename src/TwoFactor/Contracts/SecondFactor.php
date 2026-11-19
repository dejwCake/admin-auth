<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Contracts;

use Brackets\AdminAuth\TwoFactor\SecondFactorPresentation;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * One kind of second factor an admin can have (authenticator app, passkeys, …), listed in
 * `admin-auth.two_factor.factors`.
 */
interface SecondFactor
{
    public function key(): string;

    /**
     * Whether the factor's migration has run and the admin model supports it.
     */
    public function isAvailableFor(Authenticatable $user): bool;

    public function isActiveFor(Authenticatable $user): bool;

    /**
     * The factor's section of the two-factor settings page.
     */
    public function present(Authenticatable $user): SecondFactorPresentation;
}
