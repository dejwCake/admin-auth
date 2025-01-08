<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Password;

use Brackets\AdminAuth\Notifications\ResetPassword;
use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;

class ForgotPasswordTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected function createTestUser(): TestBracketsUserModel
    {
        $user = TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        $this->assertDatabaseHas('test_brackets_user_models', [
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
