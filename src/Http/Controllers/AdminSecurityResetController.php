<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Brackets\AdminAuth\Http\Requests\SecurityResetRequest;
use Brackets\AdminAuth\Notifications\SecurityResetNotification;
use Brackets\AdminAuth\Services\SecurityResetService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use function assert;

/**
 * Removes another admin's second factors (TOTP and passkeys) from the admin-users listing.
 */
final class AdminSecurityResetController extends Controller
{
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly AuthFactory $authFactory,
        private readonly SecurityResetService $securityResetService,
        private readonly Dispatcher $dispatcher,
        private readonly LoggerInterface $logger,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * The request only validates (permission, password, not self); the target comes from the route.
     *
     * @return array{message: string}
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function __invoke(SecurityResetRequest $request, string $targetAdminId): array
    {
        $actor = $this->authFactory->guard($this->guard)->user();
        assert($actor instanceof Authenticatable || $actor === null);
        $target = $this->targetAdmin($targetAdminId);

        $removed = $this->securityResetService->reset($target);
        if ($removed->isEmpty()) {
            return ['message' => trans('brackets/admin-auth::admin.security_reset.nothing_to_reset')];
        }

        $this->dispatcher->send($target, new SecurityResetNotification($this->nameOf($actor), CarbonImmutable::now()));
        $this->logger->info('Second factors of an admin were reset by another admin.', [
            'actor_id' => $this->idOf($actor),
            'target_id' => $target->getAuthIdentifier(),
            'totp' => $removed->totp,
            'passkeys' => count($removed->passkeys),
        ]);

        return ['message' => trans('brackets/admin-auth::admin.security_reset.done')];
    }

    /**
     * Through the guard's user provider, so a project's own admin model works.
     *
     * @throws NotFoundHttpException
     */
    private function targetAdmin(string $targetAdminId): Authenticatable
    {
        $guard = $this->authFactory->guard($this->guard);
        $target = method_exists($guard, 'getProvider') ? $guard->getProvider()->retrieveById($targetAdminId) : null;
        if ($target === null) {
            throw NotFoundHttpException::fromStatusCode(404, trans('Admin User not found'));
        }

        return $target;
    }

    private function idOf(?Authenticatable $actor): int|string|null
    {
        return $actor?->getAuthIdentifier();
    }

    private function nameOf(?Authenticatable $actor): string
    {
        if (!$actor instanceof Model) {
            return '';
        }

        $name = trim((string) ($actor->getAttribute('full_name') ?? $actor->getAttribute('name') ?? ''));

        return $name !== '' ? $name : (string) $actor->getAttribute('email');
    }
}
