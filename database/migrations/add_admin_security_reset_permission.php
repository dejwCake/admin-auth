<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Container\Container;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Migrations\Migration;

// Adds the permission to reset another admin's second factors, granted to the Administrator role.
// Idempotent; skipped without the permission tables (fresh installs get it from craftable's defaults).
return new class extends Migration
{
    private const string PERMISSION = 'admin.admin-user.security-reset';
    private const string ROLE = 'Administrator';

    private readonly Config $config;
    private readonly ConnectionInterface $db;
    private readonly string $guardName;

    public function __construct()
    {
        // An anonymous migration can't take constructor DI.
        $container = Container::getInstance();
        $this->config = $container->make(Config::class);
        $this->db = $container->make(ConnectionInterface::class);
        $this->guardName = (string) $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!$this->db->getSchemaBuilder()->hasTable($this->table('permissions'))) {
            return;
        }

        $this->db->transaction(function (): void {
            $permissionId = $this->permissionId() ?? $this->db->table($this->table('permissions'))->insertGetId([
                'name' => self::PERMISSION,
                'guard_name' => $this->guardName,
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ]);

            $roleId = $this->db->table($this->table('roles'))
                ->where(['name' => self::ROLE, 'guard_name' => $this->guardName])
                ->value('id');
            if ($roleId === null) {
                return;
            }

            $link = ['permission_id' => $permissionId, 'role_id' => $roleId];
            if (!$this->db->table($this->table('role_has_permissions'))->where($link)->exists()) {
                $this->db->table($this->table('role_has_permissions'))->insert($link);
            }
        });

        $this->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!$this->db->getSchemaBuilder()->hasTable($this->table('permissions'))) {
            return;
        }

        $permissionId = $this->permissionId();
        if ($permissionId === null) {
            return;
        }

        $this->db->transaction(function () use ($permissionId): void {
            foreach (['role_has_permissions', 'model_has_permissions'] as $pivot) {
                $this->db->table($this->table($pivot))->where('permission_id', $permissionId)->delete();
            }
            $this->db->table($this->table('permissions'))->where('id', $permissionId)->delete();
        });

        $this->forgetCachedPermissions();
    }

    private function permissionId(): ?int
    {
        $id = $this->db->table($this->table('permissions'))
            ->where(['name' => self::PERMISSION, 'guard_name' => $this->guardName])
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    private function table(string $name): string
    {
        return (string) $this->config->get(sprintf('permission.table_names.%s', $name), $name);
    }

    /**
     * Like spatie's PermissionRegistrar::forgetCachedPermissions(), without needing its provider.
     */
    private function forgetCachedPermissions(): void
    {
        $key = $this->config->get('permission.cache.key');
        if (is_string($key) && $key !== '') {
            Container::getInstance()->make(Cache::class)->forget($key);
        }
    }
};
