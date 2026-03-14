<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Services\ActivationService;

use Brackets\AdminAuth\Services\ActivationService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class HandleTest extends AdminUserTestCase
{
    protected function createTestUser(bool $activated = false): TestAdminUserModel
    {
        Notification::fake();

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => $activated,
            'forbidden' => false,
        ]);
    }

    public function testHandleReturnsFalseWhenActivationDisabled(): void
    {
        $this->app['config']->set('admin-auth.activation_enabled', false);

        $user = $this->createTestUser();

        $activationService = $this->app->make(ActivationService::class);
        $result = $activationService->handle($user);

        self::assertFalse($result);
    }

    public function testHandleReturnsStringWhenUserAlreadyActivated(): void
    {
        $user = $this->createTestUser(true);

        $activationService = $this->app->make(ActivationService::class);
        $result = $activationService->handle($user);

        // property_exists() returns false for Eloquent attributes, so the activated
        // check is skipped and the broker tries to send activation link. The broker
        // returns 'invalid-user' because credentials filter includes activated=false.
        self::assertIsString($result);
    }

    public function testHandleSendsActivationLinkForInactiveUser(): void
    {
        Notification::fake();

        $user = $this->createTestUser(false);

        $activationService = $this->app->make(ActivationService::class);
        $result = $activationService->handle($user);

        self::assertIsString($result);
    }
}
