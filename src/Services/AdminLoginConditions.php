<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;

final readonly class AdminLoginConditions
{
    public function __construct(private Config $config)
    {
    }

    /**
     * @return array<string, bool>
     */
    public function conditions(): array
    {
        $conditions = [];
        if ($this->config->get('admin-auth.check_forbidden')) {
            $conditions['forbidden'] = false;
        }
        if ($this->config->get('admin-auth.activation_enabled')) {
            $conditions['activated'] = true;
        }

        return $conditions;
    }

    public function allows(Authenticatable $user): bool
    {
        if (!$user instanceof Model) {
            return $this->conditions() === [];
        }

        foreach ($this->conditions() as $column => $value) {
            if ((bool) $user->getAttribute($column) !== $value) {
                return false;
            }
        }

        return true;
    }
}
