<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Notifications;

use Brackets\AdminAuth\Notifications\ResetPassword;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class ResetPasswordTest extends AdminUserTestCase
{
    public function testToMailContainsResetLink(): void
    {
        Notification::fake();

        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        $notification = $this->app->make(ResetPassword::class, ['token' => 'test-token']);

        $mailMessage = $notification->toMail($user);

        self::assertNotNull($mailMessage);
        self::assertNotEmpty($mailMessage->subject);
    }

    public function testViaReturnsMail(): void
    {
        $notification = $this->app->make(ResetPassword::class, ['token' => 'test-token']);

        $user = new TestAdminUserModel();
        $channels = $notification->via($user);

        self::assertEquals(['mail'], $channels);
    }
}
