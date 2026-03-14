<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Activation\Repositories\DatabaseTokenRepository;

use Brackets\AdminAuth\Activation\Repositories\DatabaseTokenRepository;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\TestCase;
use Illuminate\Support\Facades\Notification;

class MarkAsUsedTest extends TestCase
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
            'activated' => false,
            'forbidden' => false,
        ]);
    }

    public function testMarkAsUsedSetsUsedFlag(): void
    {
        $user = $this->createTestUser();
        $token = $this->repository->create($user);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $token,
            'used' => false,
        ]);

        $this->repository->markAsUsed($user, $token);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $token,
            'used' => true,
        ]);
    }
}
