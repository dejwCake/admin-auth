<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\ActivationEmailController;

use Brackets\AdminAuth\Activation\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class SendActivationEmailAdminUserTest extends AdminUserTestCase
{
    protected function createTestUser(bool $activated = true, bool $forbidden = false): TestAdminUserModel
    {
        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john@example.com',
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);

        return $user;
    }

    public function testCanSeeActivationForm(): void
    {
        $response = $this->get(url('/admin/activation'));
        $response->assertStatus(200);
    }

    public function testSendActivationEmailAfterUserCreated(): void
    {
        Notification::fake();

        $user = $this->createTestUser(false);

        Notification::assertSentTo($user, ActivationNotification::class);
    }

    public function testSendActivationEmailAfterUserNotActivatedAndFormFilled(): void
    {
        Notification::fake();

        $user = $this->createTestUser(false);

        $response = $this->post(url('/admin/activation/send'), ['email' => 'john@example.com']);
        $response->assertStatus(302);

        Notification::assertSentTo($user, ActivationNotification::class);
    }

    public function testDoNotSendActivationEmailIfEmailNotFound(): void
    {
        Notification::fake();

        $response = $this->post(url('/admin/activation/send'), ['email' => 'user@example.com']);
        $response->assertStatus(302);

        $user = new TestAdminUserModel([
            'email' => 'user@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => false,
            'forbidden' => false,
        ]);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }

    public function testDoNotSendActivationEmailIfUserAlreadyActivated(): void
    {
        Notification::fake();

        $user = $this->createTestUser(true);

        $response = $this->post(url('/admin/activation/send'), ['email' => 'john@example.com']);
        $response->assertStatus(302);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }
}
