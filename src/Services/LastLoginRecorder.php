<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

final readonly class LastLoginRecorder
{
    public function record(?Authenticatable $user): void
    {
        if (!$user instanceof Model) {
            return;
        }

        if ($user->getConnection()->getSchemaBuilder()->hasColumn($user->getTable(), 'last_login_at')) {
            $user->update(['last_login_at' => CarbonImmutable::now()]);
        }
    }
}
