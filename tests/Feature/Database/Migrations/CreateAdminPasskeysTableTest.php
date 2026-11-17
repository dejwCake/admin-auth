<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Database\Migrations;

use Brackets\AdminAuth\Models\AdminUser;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Migrations\Migration;
use Override;

/**
 * Runs the package's own migrations, which the test schema does not use, to check the foreign key.
 */
class CreateAdminPasskeysTableTest extends AdminUserTestCase
{
    private Migration $adminUsersMigration;

    private Migration $passkeysMigration;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder();
        // The test schema's own table has no foreign key.
        $schema->dropIfExists('admin_passkeys');
        $schema->dropIfExists('admin_users');

        $this->adminUsersMigration = require __DIR__ . '/../../../../database/migrations/create_admin_users_table.php';
        $this->passkeysMigration = require __DIR__ . '/../../../../database/migrations/create_admin_passkeys_table.php';
        $this->migrate($this->adminUsersMigration, 'up');
        $this->migrate($this->passkeysMigration, 'up');
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->migrate($this->passkeysMigration, 'down');
        $this->migrate($this->adminUsersMigration, 'down');

        parent::tearDown();
    }

    public function testForceDeletingAnAdminRemovesTheirPasskeys(): void
    {
        $adminId = $this->insertAdminWithPasskeys(2);

        $this->db()->table('admin_users')->where('id', $adminId)->delete();

        self::assertSame(0, $this->db()->table('admin_passkeys')->count());
    }

    public function testSoftDeletingAnAdminKeepsTheirPasskeys(): void
    {
        $adminId = $this->insertAdminWithPasskeys(2);

        AdminUser::query()->findOrFail($adminId)->delete();

        self::assertNotNull($this->db()->table('admin_users')->where('id', $adminId)->value('deleted_at'));
        self::assertSame(2, $this->db()->table('admin_passkeys')->count());
    }

    private function insertAdminWithPasskeys(int $count): int
    {
        $now = CarbonImmutable::now();
        $adminId = (int) $this->db()->table('admin_users')->insertGetId([
            'email' => 'john@example.com',
            'password' => 'not-used',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $this->db()->table('admin_passkeys')->insert([
                'admin_user_id' => $adminId,
                'name' => sprintf('Passkey %d', $i),
                'credential_id' => sprintf('credential-%d', $i),
                'credential' => '{}',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return $adminId;
    }

    /**
     * The migrations are anonymous classes; the Migration base class declares neither up() nor down().
     */
    private function migrate(Migration $migration, string $direction): void
    {
        $migration->{$direction}();
    }

    private function db(): DatabaseManager
    {
        return $this->app->make(DatabaseManager::class);
    }
}
