<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor\Factors;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\SecondFactorPresentation;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Routing\UrlGenerator;
use Override;

/**
 * The authenticator app (TOTP), managed in place by the `two-factor-settings` component.
 */
final readonly class TotpFactor implements SecondFactor
{
    public const string KEY = TwoFactorService::FACTOR_TOTP;

    public function __construct(private TwoFactorService $twoFactorService, private UrlGenerator $urlGenerator)
    {
    }

    #[Override]
    public function key(): string
    {
        return self::KEY;
    }

    #[Override]
    public function isAvailableFor(Authenticatable $user): bool
    {
        return $this->twoFactorService->isAvailableFor($user);
    }

    #[Override]
    public function isActiveFor(Authenticatable $user): bool
    {
        return $this->isAvailableFor($user) && $this->twoFactorService->isEnabledFor($user);
    }

    #[Override]
    public function present(Authenticatable $user): SecondFactorPresentation
    {
        $active = $this->isActiveFor($user);

        return new SecondFactorPresentation(
            key: self::KEY,
            name: trans('brackets/admin-auth::admin.two_factor.factors.totp.name'),
            icon: 'fa fa-mobile-screen',
            available: $this->isAvailableFor($user),
            active: $active,
            status: $active
                ? trans('brackets/admin-auth::admin.two_factor.status_on')
                : trans('brackets/admin-auth::admin.two_factor.status_not_set_up'),
            notice: trans('brackets/admin-auth::admin.two_factor.migration_missing'),
            component: 'two-factor-settings',
            props: [
                'enabled' => $active,
                'required' => $this->twoFactorService->isRequiredFor($user),
                'canDisable' => $this->twoFactorService->canRemoveFactor($user, self::KEY),
                'enableUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/enable'),
                'confirmUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/confirm'),
                'recoveryCodesUrl' => $this->urlGenerator->route(
                    'brackets/admin-auth::admin/two-factor/recovery-codes',
                ),
                'disableUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/disable'),
                'translations' => $this->translations(),
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function translations(): array
    {
        return [
            'title' => trans('brackets/admin-auth::admin.two_factor.factors.totp.name'),
            'status_off' => trans('brackets/admin-auth::admin.two_factor.status_not_set_up'),
            'cancel' => trans('brackets/admin-ui::admin.btn.cancel'),
            'status_on' => trans('brackets/admin-auth::admin.two_factor.status_on'),
            'enable' => trans('brackets/admin-auth::admin.two_factor.enable'),
            'enable_description' => trans('brackets/admin-auth::admin.two_factor.enable_description'),
            'scan_qr' => trans('brackets/admin-auth::admin.two_factor.scan_qr'),
            'secret_label' => trans('brackets/admin-auth::admin.two_factor.secret_label'),
            'code' => trans('brackets/admin-auth::admin.two_factor.code'),
            'confirm' => trans('brackets/admin-auth::admin.two_factor.confirm'),
            'recovery_codes_title' => trans('brackets/admin-auth::admin.two_factor.recovery_codes_title'),
            'recovery_codes_description' => trans('brackets/admin-auth::admin.two_factor.recovery_codes_description'),
            'copy' => trans('brackets/admin-auth::admin.two_factor.copy'),
            'copied' => trans('brackets/admin-auth::admin.two_factor.copied'),
            'download' => trans('brackets/admin-auth::admin.two_factor.download'),
            'done' => trans('brackets/admin-auth::admin.two_factor.done'),
            'current_password' => trans('brackets/admin-auth::admin.two_factor.current_password'),
            'regenerate' => trans('brackets/admin-auth::admin.two_factor.regenerate'),
            'disable' => trans('brackets/admin-auth::admin.two_factor.disable'),
            'required_notice' => trans('brackets/admin-auth::admin.two_factor.required_notice'),
        ];
    }
}
