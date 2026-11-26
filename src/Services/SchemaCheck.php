<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;

final class SchemaCheck
{
    /** @var array<string, true> */
    private array $present = [];

    public function hasTable(Model $model): bool
    {
        return $this->remember(
            sprintf('%s:%s', $model->getConnectionName() ?? '', $model->getTable()),
            static fn (): bool => $model->getConnection()->getSchemaBuilder()->hasTable($model->getTable()),
        );
    }

    public function hasColumn(Model $model, string $column): bool
    {
        return $this->remember(
            sprintf('%s:%s.%s', $model->getConnectionName() ?? '', $model->getTable(), $column),
            static fn (): bool => $model->getConnection()->getSchemaBuilder()->hasColumn($model->getTable(), $column),
        );
    }

    /**
     * @param Closure(): bool $check
     */
    private function remember(string $key, Closure $check): bool
    {
        if (isset($this->present[$key])) {
            return true;
        }
        if (!$check()) {
            return false;
        }
        $this->present[$key] = true;

        return true;
    }
}
