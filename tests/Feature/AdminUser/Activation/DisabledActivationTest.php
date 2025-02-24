<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Activation;

use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;

class DisabledActivationTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected string $token = '123456aabbcc';

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('admin-auth.activation_enabled', false);
    }

    protected function createTestUser(
        bool $activated = true,
        bool $forbidden = false,
        bool $used = false,
        ?CarbonInterface $activationCreatedAt = null,
    ): TestBracketsUserModel {
        $user = TestBracketsUserModel::create([
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

        //create also activation
        $this->app['db']->connection()->table('admin_activations')->insert([
            'email' => $user->email,
            'token' => $this->token,
            'used' => $used,
            'created_at' => $activationCreatedAt ?? CarbonImmutable::now(),
        ]);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => $used,
        ]);

        return $user;
    }

    public function testDoNotSendActivationMailAfterUserCreated(): void
    {
        Notification::fake();

        $user = $this->createTestUser(false);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }

    public function testDoNotSendActivationMailFormFilled(): void
    {
        Notification::fake();

        $user = $this->createTestUser(false);

        $response = $this->post(url('/admin/activation/send'), ['email' => 'john@example.com']);
        $response->assertStatus(302);

        Notification::assertNotSentTo($user, ActivationNotification::class);
    }

    public function testDoNotActivateUserIfActivationDisabled(): void
    {
        $this->createTestUser(false);

        $response = $this->get(route('brackets/admin-auth::admin/activation/activate', ['token' => $this->token]));
        $response->assertStatus(302);

        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();
        self::assertEquals(0, $userNew->activated);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => false,
        ]);
    }
}
