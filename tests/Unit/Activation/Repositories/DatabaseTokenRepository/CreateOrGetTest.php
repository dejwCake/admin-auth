<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Activation\Repositories\DatabaseTokenRepository;

use Brackets\AdminAuth\Activation\Repositories\DatabaseTokenRepository;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Override;

class CreateOrGetTest extends TestCase
{
    protected string $adminAuthGuard = 'admin';

    private DatabaseTokenRepository $repository;

    #[Override]
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

    public function testCreateOrGetReturnsExistingValidToken(): void
    {
        $user = $this->createTestUser();
        $originalToken = $this->repository->create($user);

        $retrievedToken = $this->repository->createOrGet($user);

        self::assertEquals($originalToken, $retrievedToken);
        $this->assertDatabaseCount('admin_activations', 1);
    }

    public function testCreateOrGetCreatesNewTokenWhenNoneExists(): void
    {
        $user = $this->createTestUser();

        $token = $this->repository->createOrGet($user);

        self::assertIsString($token);
        self::assertNotEmpty($token);
        $this->assertDatabaseCount('admin_activations', 1);
    }
}
