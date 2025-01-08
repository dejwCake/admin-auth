<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\StandardUser\Password;

use Brackets\AdminAuth\Notifications\ResetPassword;
use Brackets\AdminAuth\Tests\Models\TestStandardUserModel;
use Brackets\AdminAuth\Tests\StandardTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;

class ForgotPasswordTest extends StandardTestCase
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

    public function testCanSeeForgotPasswordForm(): void
    {
        $response = $this->get(url('/admin/password-reset'));
        $response->assertStatus(200);
    }

    public function testSendForgotPasswordEmailAfterFormFilled(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        $response = $this->post(url('/admin/password-reset/send'), ['email' => 'john@example.com']);
        $response->assertStatus(302);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testDoNotSendPasswordEmailIfEmailNotFound(): void
    {
        Notification::fake();

        $user = $this->createTestUser();

        $response = $this->post(url('/admin/password-reset/send'), ['email' => 'john1@example.com']);
        $response->assertStatus(302);

        Notification::assertNotSentTo($user, ResetPassword::class);
    }
}
