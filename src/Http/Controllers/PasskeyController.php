<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Brackets\AdminAuth\Http\Requests\StorePasskeyRequest;
use Brackets\AdminAuth\Http\Requests\TwoFactorPasswordRequest;
use Brackets\AdminAuth\Http\Requests\UpdatePasskeyRequest;
use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Services\TwoFactorService;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The logged-in admin's own passkeys: list, add (with the current password), rename and remove. Without the
 * migration, or with an admin model without passkeys, the page shows a notice and the actions answer 409.
 */
final class PasskeyController extends Controller
{
    private readonly string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly PasskeyService $passkeyService,
        private readonly TwoFactorService $twoFactorService,
        private readonly UrlGenerator $urlGenerator,
        private readonly ViewFactory $viewFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    public function index(Request $request): View
    {
        $user = $this->passkeyUser($request);

        return $this->viewFactory->make('brackets/admin-auth::admin.passkeys.index', [
            'available' => $user !== null,
            'passkeys' => $user?->passkeys()->orderBy('id')->get()->map($this->present(...))->all() ?? [],
            'optionsUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/passkeys/options'),
            'storeUrl' => $this->urlGenerator->route('brackets/admin-auth::admin/passkeys/store'),
            'passkeyUrl' => $this->urlGenerator->route(
                'brackets/admin-auth::admin/passkeys/update',
                ['adminPasskey' => '__id__'],
            ),
        ]);
    }

    /**
     * Options to create a passkey, only for the admin's correct current password.
     *
     * @return array<string, array|string|int|float|bool|null>|JsonResponse
     */
    public function options(TwoFactorPasswordRequest $request): array|JsonResponse
    {
        $user = $this->passkeyUser($request);
        if ($user === null) {
            return $this->unavailable();
        }

        return $this->passkeyService->registrationOptions($request->session(), $user);
    }

    /**
     * Store the passkey the browser created; it counts as passing two-factor in this session.
     */
    public function store(StorePasskeyRequest $request): JsonResponse
    {
        $user = $this->passkeyUser($request);
        if ($user === null) {
            return $this->unavailable();
        }

        $passkey = $this->passkeyService->register(
            $request->session(),
            $user,
            $request->credential(),
            (string) $request->validated('name'),
        );
        $this->twoFactorService->forgetUnconfirmedEnrolment($user);

        return new JsonResponse([
            'message' => trans('brackets/admin-auth::admin.passkeys.added'),
            'passkey' => $this->present($passkey),
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Only the admin's own passkey: another admin's one is a 404.
     *
     * @return array{message: string, passkey: array<string, int|string|null>}|JsonResponse
     */
    public function update(UpdatePasskeyRequest $request, string $adminPasskey): array|JsonResponse
    {
        $user = $this->passkeyUser($request);
        if ($user === null) {
            return $this->unavailable();
        }

        $passkey = $user->passkeys()->findOrFail($adminPasskey);
        $passkey->update(['name' => (string) $request->validated('name')]);

        return [
            'message' => trans('brackets/admin-auth::admin.passkeys.renamed'),
            'passkey' => $this->present($passkey),
        ];
    }

    /**
     * Remove the admin's own passkey with the current password, unless it is the last factor of a forced admin.
     *
     * @return array{message: string}|JsonResponse
     */
    public function destroy(TwoFactorPasswordRequest $request, string $adminPasskey): array|JsonResponse
    {
        $user = $this->passkeyUser($request);
        if ($user === null) {
            return $this->unavailable();
        }

        $passkey = $user->passkeys()->findOrFail($adminPasskey);
        if (!$this->twoFactorService->canRemoveFactor($user, TwoFactorService::FACTOR_PASSKEY)) {
            return new JsonResponse(
                ['message' => trans('brackets/admin-auth::admin.passkeys.cannot_remove_last')],
                JsonResponse::HTTP_CONFLICT,
            );
        }

        $passkey->delete();

        return ['message' => trans('brackets/admin-auth::admin.passkeys.removed')];
    }

    /**
     * What the page shows of a passkey: never the credential.
     *
     * @return array{id: int, name: string, created_at: ?string, last_used_at: ?string}
     */
    private function present(AdminPasskey $passkey): array
    {
        return [
            'id' => $passkey->id,
            'name' => $passkey->name,
            'created_at' => $passkey->created_at?->isoFormat('lll'),
            'last_used_at' => $passkey->last_used_at?->isoFormat('lll'),
        ];
    }

    /**
     * The admin, when their model supports passkeys and the migration has run.
     */
    private function passkeyUser(Request $request): ?PasskeyAuthenticatable
    {
        $user = $request->user($this->guard);

        return $user instanceof PasskeyAuthenticatable && $this->passkeyService->isAvailable() ? $user : null;
    }

    private function unavailable(): JsonResponse
    {
        return new JsonResponse(
            ['message' => trans('brackets/admin-auth::admin.passkeys.migration_missing')],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
