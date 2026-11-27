<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Services\SchemaCheck;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Builder;

/**
 * The passkeys table on the admin's connection: of the given admin, or before any admin is known (the passwordless
 * login) of the admin guard's model.
 */
final readonly class PasskeyTable
{
    public function __construct(
        private Config $config,
        private AuthFactory $authFactory,
        private SchemaCheck $schemaCheck,
    ) {
    }

    /**
     * Whether the table exists (the migration has run).
     */
    public function exists(?PasskeyAuthenticatable $user = null): bool
    {
        return $this->schemaCheck->hasTable($this->model($user));
    }

    /**
     * @return Builder<AdminPasskey>
     */
    public function query(): Builder
    {
        return $this->model(null)->newQuery();
    }

    private function model(?PasskeyAuthenticatable $user): AdminPasskey
    {
        if ($user === null) {
            $guard = $this->authFactory->guard($this->config->get('admin-auth.defaults.guard', 'admin'));
            $provider = method_exists($guard, 'getProvider') ? $guard->getProvider() : null;
            $model = $provider instanceof EloquentUserProvider ? $provider->createModel() : null;
            $user = $model instanceof PasskeyAuthenticatable ? $model : null;
        }

        return $user?->passkeys()->getRelated() ?? new AdminPasskey();
    }
}
