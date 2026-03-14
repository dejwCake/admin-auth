<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Models\AdminUser;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class GetFullNameAttributeTest extends AdminUserTestCase
{
    public function testReturnsFirstAndLastName(): void
    {
        Notification::fake();

        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'first_name' => 'John',
            'last_name' => 'Doe',
            'activated' => true,
            'forbidden' => false,
        ]);

        self::assertEquals('John Doe', $user->full_name);
    }

    public function testHandlesNullNames(): void
    {
        Notification::fake();

        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        self::assertEquals(' ', $user->full_name);
    }
}
