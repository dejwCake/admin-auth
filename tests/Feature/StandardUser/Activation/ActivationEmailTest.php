<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\StandardUser\Activation;

use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\Models\TestStandardUserModel;
use Brackets\AdminAuth\Tests\StandardTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;

class ActivationEmailTest extends StandardTestCase
{
    use DatabaseMigrations;

    protected function createTestUser(): TestStandardUserModel
    {
        $user = TestStandardUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        $this->assertDatabaseHas('test_standard_user_models', [
            'email' => 'john@example.com',
        ]);

        return $user;
    }

    public function testCanSeeActivationForm(): void
    {
        $response = $this->get(url('/admin/activation'));
        $response->assertStatus(200);
    }

    public function testDoNotSendActivationEmailAfterUserCreated(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }

    public function testDoNotSendActivationEmailAfterUserNotActivatedAndFormFilled(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        $response = $this->post(url('/admin/activation/send'), ['email' => 'john@example.com']);
        $response->assertStatus(302);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }

    public function testDoNotSendActivationEmailIfEmailNotFound(): void
    {
        Notification::fake();

        $response = $this->post(url('/admin/activation/send'), ['email' => 'user@example.com']);
        $response->assertStatus(302);

        $user = new TestStandardUserModel([
            'email' => 'user@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }
}
