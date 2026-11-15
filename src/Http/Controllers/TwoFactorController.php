<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Brackets\AdminAuth\Http\Requests\ConfirmTwoFactorRequest;
use Brackets\AdminAuth\Http\Requests\TwoFactorPasswordRequest;
use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class TwoFactorController extends Controller
{
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly TwoFactorService $twoFactorService,
        private readonly TwoFactorSession $twoFactorSession,
        private readonly UrlGenerator $urlGenerator,
        private readonly ViewFactory $viewFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * Show whether two-factor is on and what can be done in that state.
     */
    public function show(Request $request): View
    {
        // Any admin model: one without two-factor support gets the same notice as a missing migration.
        $user = $request->user($this->guard);
        if ($user === null) {
            throw NotFoundHttpException::fromStatusCode(404, trans('Admin User not found'));
        }

        return $this->viewFactory->make('brackets/admin-auth::admin.two-factor.settings', [
            'available' => $this->twoFactorService->isAvailableFor($user),
            'enabled' => $this->twoFactorService->isEnabledFor($user),
            'required' => $this->twoFactorService->isRequiredFor($user),
            'enableUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/enable'),
            'confirmUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/confirm'),
            'recoveryCodesUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/recovery-codes'),
            'disableUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/two-factor/disable'),
        ]);
    }

    /**
     * Start enrolment: a new secret with its QR code. Two-factor stays off until it is confirmed.
     *
     * @return array{secret: string, qr_code: string}|JsonResponse
     */
    public function enable(Request $request): array|JsonResponse
    {
        $user = $this->getUser($request);

        $conflict = $this->checkEnrolment($user);
        if ($conflict !== null) {
            return $conflict;
        }

        return $this->twoFactorService->startEnrolment($user);
    }

    /**
     * Confirm enrolment with a code; the session counts as verified and the recovery codes are shown once.
     *
     * @return array{recovery_codes: array<int, string>, message: string}|JsonResponse
     */
    public function confirm(ConfirmTwoFactorRequest $request): array|JsonResponse
    {
        $user = $this->getUser($request);

        $conflict = $this->checkEnrolment($user);
        if ($conflict !== null) {
            return $conflict;
        }

        $recoveryCodes = $this->twoFactorService->confirmEnrolment($user, (string) $request->validated('code'));
        $this->twoFactorSession->markVerified($request->session(), $user);

        return [
            'recovery_codes' => $recoveryCodes,
            'message' => trans('brackets/admin-auth::admin.two_factor.enabled'),
        ];
    }

    /**
     * Replace every recovery code; the new ones are shown once.
     *
     * @return array{recovery_codes: array<int, string>, message: string}|JsonResponse
     */
    public function regenerateRecoveryCodes(TwoFactorPasswordRequest $request): array|JsonResponse
    {
        $user = $this->getUser($request);

        if (!$this->twoFactorService->isEnabledFor($user)) {
            return $this->conflict('brackets/admin-auth::admin.two_factor.not_enabled');
        }

        return [
            'recovery_codes' => $this->twoFactorService->regenerateRecoveryCodes($user),
            'message' => trans('brackets/admin-auth::admin.two_factor.recovery_codes_regenerated'),
        ];
    }

    /**
     * Turn two-factor off, unless it is required for this admin.
     *
     * @return array{message: string}|JsonResponse
     */
    public function disable(TwoFactorPasswordRequest $request): array|JsonResponse
    {
        $user = $this->getUser($request);

        if ($this->twoFactorService->isRequiredFor($user)) {
            return $this->conflict('brackets/admin-auth::admin.two_factor.cannot_disable_required');
        }

        $this->twoFactorService->disable($user);

        return ['message' => trans('brackets/admin-auth::admin.two_factor.disabled')];
    }

    /**
     * Enrolment needs the migration, and must not replace an active setup.
     */
    private function checkEnrolment(Model&TwoFactorAuthenticatable $user): ?JsonResponse
    {
        if (!$this->twoFactorService->isAvailableFor($user)) {
            return $this->conflict('brackets/admin-auth::admin.two_factor.migration_missing');
        }

        if ($this->twoFactorService->isEnabledFor($user)) {
            return $this->conflict('brackets/admin-auth::admin.two_factor.already_enabled');
        }

        return null;
    }

    private function conflict(string $translationKey): JsonResponse
    {
        return new JsonResponse(['message' => trans($translationKey)], JsonResponse::HTTP_CONFLICT);
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getUser(Request $request): Model&TwoFactorAuthenticatable
    {
        $user = $request->user($this->guard);
        if (!$user instanceof Model || !$user instanceof TwoFactorAuthenticatable) {
            throw NotFoundHttpException::fromStatusCode(404, trans('Admin User not found'));
        }

        return $user;
    }
}
