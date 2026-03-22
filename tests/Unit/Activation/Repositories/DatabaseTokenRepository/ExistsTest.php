<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Activation\Repositories\DatabaseTokenRepository;

use Brackets\AdminAuth\Activation\Repositories\DatabaseTokenRepository;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;
use Override;

class ExistsTest extends TestCase
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

    public function testExistsReturnsTrueForValidToken(): void
    {
        $user = $this->createTestUser();
        $token = $this->repository->create($user);

        self::assertTrue($this->repository->exists($user, $token));
    }

    public function testExistsReturnsFalseForExpiredToken(): void
    {
        $user = $this->createTestUser();

        $this->app['db']->connection()->table('admin_activations')->insert([
            'email' => 'john@example.com',
            'token' => 'expired-token',
            'used' => false,
            'created_at' => CarbonImmutable::now()->subHours(2),
        ]);

        self::assertFalse($this->repository->exists($user, 'expired-token'));
    }

    public function testExistsReturnsFalseForUsedToken(): void
    {
        $user = $this->createTestUser();

        $this->app['db']->connection()->table('admin_activations')->insert([
            'email' => 'john@example.com',
            'token' => 'used-token',
            'used' => true,
            'created_at' => CarbonImmutable::now(),
        ]);

        self::assertFalse($this->repository->exists($user, 'used-token'));
    }

    public function testExistsReturnsFalseForNonExistentToken(): void
    {
        $user = $this->createTestUser();

        self::assertFalse($this->repository->exists($user, 'nonexistent-token'));
    }
}
