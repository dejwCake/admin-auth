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
 * Runs the package's own migrations, which the test schema does not use, to check the keys.
 */
final class CreateAdminTwoFactorsTableTest extends AdminUserTestCase
{
    private Migration $adminUsersMigration;

    private Migration $twoFactorsMigration;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $schema = $this->db()->connection()->getSchemaBuilder();
        // The test schema's own table has no foreign key.
        $schema->dropIfExists('admin_two_factors');
        $schema->dropIfExists('admin_users');

        $this->adminUsersMigration = require sprintf(
            '%s/../../../../database/migrations/create_admin_users_table.php',
            __DIR__,
        );
        $this->twoFactorsMigration = require sprintf(
            '%s/../../../../database/migrations/create_admin_two_factors_table.php',
            __DIR__,
        );
        $this->migrate($this->adminUsersMigration, 'up');
        $this->migrate($this->twoFactorsMigration, 'up');
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->migrate($this->twoFactorsMigration, 'down');
        $this->migrate($this->adminUsersMigration, 'down');

        parent::tearDown();
    }

    public function testForceDeletingAnAdminRemovesTheirTwoFactor(): void
    {
        $adminId = $this->insertAdminWithTwoFactor();

        $this->db()->table('admin_users')->where('id', $adminId)->delete();

        self::assertSame(0, $this->db()->table('admin_two_factors')->count());
    }

    public function testSoftDeletingAnAdminKeepsTheirTwoFactor(): void
    {
        $adminId = $this->insertAdminWithTwoFactor();

        AdminUser::query()->findOrFail($adminId)->delete();

        self::assertSame(1, $this->db()->table('admin_two_factors')->count());
    }

    public function testAnAdminHasOneRecordAtMost(): void
    {
        $indexes = $this->db()->connection()->getSchemaBuilder()->getIndexes('admin_two_factors');

        $unique = array_filter(
            $indexes,
            static fn (array $index): bool => $index['unique'] && $index['columns'] === ['admin_user_id'],
        );
        self::assertCount(1, $unique);
    }

    private function insertAdminWithTwoFactor(): int
    {
        $now = CarbonImmutable::now();
        $adminId = (int) $this->db()->table('admin_users')->insertGetId([
            'email' => 'john@example.com',
            'password' => 'not-used',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->db()->table('admin_two_factors')->insert([
            'admin_user_id' => $adminId,
            'secret' => 'encrypted-secret',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

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
