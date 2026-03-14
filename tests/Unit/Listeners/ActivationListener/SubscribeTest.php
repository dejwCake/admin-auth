<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Listeners\ActivationListener;

use Brackets\AdminAuth\Activation\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class SubscribeTest extends AdminUserTestCase
{
    public function testActivationEmailSentOnUserCreation(): void
    {
        Notification::fake();

        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => false,
            'forbidden' => false,
        ]);

        Notification::assertSentTo($user, ActivationNotification::class);
    }
}
