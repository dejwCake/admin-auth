<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Activation\Notifications;

use Brackets\AdminAuth\Activation\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class ActivationNotificationTest extends AdminUserTestCase
{
    public function testToMailContainsActivationLink(): void
    {
        Notification::fake();

        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        $notification = $this->app->make(ActivationNotification::class, ['token' => 'test-token']);

        $mailMessage = $notification->toMail($user);

        self::assertNotNull($mailMessage);
        self::assertNotEmpty($mailMessage->subject);
    }

    public function testViaReturnsMail(): void
    {
        $notification = $this->app->make(ActivationNotification::class, ['token' => 'test-token']);

        $user = new TestAdminUserModel();
        $channels = $notification->via($user);

        self::assertEquals(['mail'], $channels);
    }
}
