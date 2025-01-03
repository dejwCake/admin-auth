<?php

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Activation;

use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Notification;

class ActivationTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->token = '123456aabbcc';
    }

    protected function createTestUser(
        bool $activated = true,
        bool $forbidden = false,
        bool $used = false,
        Carbon $activationCreatedAt = null
    ): TestBracketsUserModel {
        Notification::fake();
        // TODO maybe we can Mock sending an email to speed up a test?
        $user = TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);
        if ($activated === false) {
            Notification::assertSentTo(
                $user,
                ActivationNotification::class
            );
        }

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
            'created_at' => $activationCreatedAt ?? Carbon::now(),
        ]);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => $used,
        ]);

        return $user;
    }

    public function testActivateUserIfTokenIsOk(): void
    {
        $user = $this->createTestUser(false);

        $response = $this->get(route('brackets/admin-auth::admin/activation/activate', ['token' => $this->token]));
        $response->assertStatus(302);


        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertTrue($userNew->activated);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => true,
        ]);
    }

    public function testDoNotActivateUserIfTokenDoesNotExists(): void
    {
        $user = $this->createTestUser(false);

        $response = $this->get(route(
            'brackets/admin-auth::admin/activation/activate',
            ['token' => $this->token . '11']
        ));
        $response->assertStatus(302);


        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();
        self::assertEquals(0, $userNew->activated);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => false,
        ]);
    }

    public function testDoNotActivateUserIfTokenUsed(): void
    {
        $user = $this->createTestUser(false, false, true);

        $response = $this->get(route('brackets/admin-auth::admin/activation/activate', ['token' => $this->token]));
        $response->assertStatus(302);


        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();
        self::assertEquals(0, $userNew->activated);

        $this->assertDatabaseHas('admin_activations', [
            'email' => 'john@example.com',
            'token' => $this->token,
            'used' => true,
        ]);
    }

    public function testDoNotActivateUserIfTokenExpired(): void
    {
        $user = $this->createTestUser(false, false, false, Carbon::now()->subDays(10));

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
