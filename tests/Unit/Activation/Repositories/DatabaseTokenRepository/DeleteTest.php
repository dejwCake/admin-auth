<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Activation\Repositories\DatabaseTokenRepository;

use Brackets\AdminAuth\Activation\Repositories\DatabaseTokenRepository;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class DeleteTest extends TestCase
{
    protected string $adminAuthGuard = 'admin';

    private DatabaseTokenRepository $repository;

    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->repository = new DatabaseTokenRepository(
            $this->app['db']->connection(),
            $this->app['hash'],
            'admin_activations',
            'test-key',
            60,
        );
    }

    protected function createTestUser(): TestAdminUserModel
    {
        Notification::fake();

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);
    }

    public function testDeleteRemovesAllTokensForUser(): void
    {
        $user = $this->createTestUser();
        $this->repository->create($user);
        $this->repository->create($user);

        $this->assertDatabaseCount('admin_activations', 2);

        $this->repository->delete($user);

        $this->assertDatabaseCount('admin_activations', 0);
    }
}
