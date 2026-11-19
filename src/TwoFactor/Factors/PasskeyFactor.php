<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Factors;

use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorLink;
use Brackets\AdminAuth\TwoFactor\SecondFactorPresentation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Override;

/**
 * The admin's passkeys, managed on the passkeys page: the section shows their number and links there.
 */
final readonly class PasskeyFactor implements SecondFactor
{
    public const string KEY = TwoFactorService::FACTOR_PASSKEY;

    public function __construct(
        private PasskeyService $passkeyService,
        private UrlGenerator $urlGenerator,
        private Translator $translator,
    ) {
    }

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function isAvailableFor(Authenticatable $user): bool
    {
        return $this->passkeyService->isAvailableFor($user);
    }

    #[Override]
    public function isActiveFor(Authenticatable $user): bool
    {
        return $this->passkeyService->hasPasskeys($user);
    }

    #[Override]
    public function present(Authenticatable $user): SecondFactorPresentation
    {
        $count = $this->passkeyService->countFor($user);

        return new SecondFactorPresentation(
            key: self::KEY,
            name: trans('brackets/admin-auth::admin.two_factor.factors.passkey.name'),
            icon: 'fa fa-fingerprint',
            available: $this->isAvailableFor($user),
            active: $count > 0,
            status: $count > 0
                ? $this->translator->choice('brackets/admin-auth::admin.two_factor.factors.passkey.count', $count)
                : trans('brackets/admin-auth::admin.two_factor.factors.passkey.none'),
            notice: trans('brackets/admin-auth::admin.passkeys.migration_missing'),
            link: new SecondFactorLink(
                $this->urlGenerator->route('brackets/admin-auth::admin/passkeys'),
                $count > 0
                    ? trans('brackets/admin-auth::admin.two_factor.factors.passkey.manage')
                    : trans('brackets/admin-auth::admin.two_factor.factors.passkey.add'),
            ),
        );
    }
}
