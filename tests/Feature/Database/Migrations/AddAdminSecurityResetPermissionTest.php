<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Database\Migrations;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Override;

/**
 * Runs the package's permission migration on minimal permission tables.
 */
class AddAdminSecurityResetPermissionTest extends AdminUserTestCase
{
    private const string PERMISSION = 'admin.admin-user.security-reset';
    private const array TABLES = ['role_has_permissions', 'model_has_permissions', 'roles', 'permissions'];

    #[Override]
    protected function tearDown(): void
    {
        $this->dropPermissionTables();

        parent::tearDown();
    }

    public function testAdministratorGetsThePermission(): void
    {
        $this->createPermissionTables();
        $roleId = $this->insertRole('Administrator');

        $this->migrate('up');

        $permissionId = $this->permissionId();
        self::assertNotNull($permissionId);
        self::assertTrue(
            $this->db()->table('role_has_permissions')->where(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
            )->exists(),
        );
    }

    public function testRunningTwiceKeepsOnePermissionAndOneGrant(): void
    {
        $this->createPermissionTables();
        $this->insertRole('Administrator');

        $this->migrate('up');
        $this->migrate('up');

        self::assertSame(1, $this->db()->table('permissions')->where('name', self::PERMISSION)->count());
        self::assertSame(1, $this->db()->table('role_has_permissions')->count());
    }

    public function testWithoutTheAdministratorRoleThePermissionIsUnassigned(): void
    {
        $this->createPermissionTables();
        $this->insertRole('Editor');

        $this->migrate('up');

        self::assertNotNull($this->permissionId());
        self::assertSame(0, $this->db()->table('role_has_permissions')->count());
    }

    public function testRollbackRemovesThePermissionAndItsGrants(): void
    {
        $this->createPermissionTables();
        $this->insertRole('Administrator');
        $this->migrate('up');

        $this->migrate('down');

        self::assertNull($this->permissionId());
        self::assertSame(0, $this->db()->table('role_has_permissions')->count());
    }

    public function testWithoutPermissionTablesItIsSkipped(): void
    {
        $this->migrate('up');
        $this->migrate('down');

        self::assertFalse($this->db()->connection()->getSchemaBuilder()->hasTable('permissions'));
    }

    private function migrate(string $direction): void
    {
        $migration = require __DIR__ . '/../../../../database/migrations/add_admin_security_reset_permission.php';
        \assert($migration instanceof Migration);
        // Anonymous migration: the Migration base class declares neither up() nor down().
        $migration->{$direction}();
    }

    private function permissionId(): ?int
    {
        $id = $this->db()->table('permissions')->where(['name' => self::PERMISSION, 'guard_name' => 'admin'])->value(
            'id',
        );

        return $id === null ? null : (int) $id;
    }

    private function insertRole(string $name): int
    {
        return (int) $this->db()->table('roles')->insertGetId([
            'name' => $name,
            'guard_name' => 'admin',
            'created_at' => CarbonImmutable::now(),
            'updated_at' => CarbonImmutable::now(),
        ]);
    }

    private function createPermissionTables(): void
    {
        $schema = $this->db()->connection()->getSchemaBuilder();
        $this->dropPermissionTables();
        foreach (['permissions', 'roles'] as $table) {
            $schema->create($table, static function (Blueprint $blueprint): void {
                $blueprint->id();
                $blueprint->string('name');
                $blueprint->string('guard_name');
                $blueprint->timestamps();
            });
        }
        $schema->create('role_has_permissions', static function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('permission_id');
            $blueprint->unsignedBigInteger('role_id');
        });
        $schema->create('model_has_permissions', static function (Blueprint $blueprint): void {
            $blueprint->unsignedBigInteger('permission_id');
            $blueprint->string('model_type');
            $blueprint->unsignedBigInteger('model_id');
        });
    }

    private function dropPermissionTables(): void
    {
        $schema = $this->db()->connection()->getSchemaBuilder();
        foreach (self::TABLES as $table) {
            $schema->dropIfExists($table);
        }
    }

    private function db(): DatabaseManager
    {
        return $this->app->make(DatabaseManager::class);
    }
}
