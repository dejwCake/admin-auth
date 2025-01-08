<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Auth;

use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class LoginBracketsTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected function createTestUser(bool $activated = true, bool $forbidden = false): TestBracketsUserModel
    {
        Notification::fake();
        $user = TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);
        if ($activated === false) {
            Notification::assertSentTo($user, ActivationNotification::class);
        }

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john@example.com',
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);

        return $user;
    }

    public function testLoginPageIsAccessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function testUserCanLogIn(): void
    {
        $user = $this->createTestUser();
        self::assertNull($user->last_login_at);

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertNotEmpty(Auth::guard($this->adminAuthGuard)->user());
        self::assertNotNull(Auth::guard($this->adminAuthGuard)->user()->last_login_at);
    }

    public function testUserWithWrongCredentialsCannotLogIn(): void
    {
        $this->createTestUser();

        $response = $this->json('post', '/admin/login', ['email' => 'john@example.com', 'password' => 'testpass1231']);
        $response->assertStatus(422);

        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testNotActivatedUserCannotLogIn(): void
    {
        $this->createTestUser(false);

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);


        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testNotActivatedUserCanLogInIfActivationDisabled(): void
    {
        $this->createTestUser(false);

        $this->app['config']->set('admin-auth.activation_enabled', false);

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertNotEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testForbiddenUserCannotLogIn(): void
    {
        $this->createTestUser(true, true);

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testDeletedUserCannotLogIn(): void
    {
        $time = CarbonImmutable::now();
        //Delted at is not fillable, therefore we need to unguard to force fill
        TestBracketsUserModel::unguard();
        TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
            'deleted_at' => $time,
        ]);
        TestBracketsUserModel::reguard();

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john@example.com',
            'activated' => true,
            'forbidden' => false,
            'deleted_at' => $time,
        ]);

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testAlreadyAuthUserIsRedirectedFromLogin(): void
    {
        $this->createTestUser();

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);
        $response->assertRedirect('/admin');

        self::assertNotEmpty(Auth::guard($this->adminAuthGuard)->user());

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);
        $response->assertRedirect($this->app['config']->get('admin-auth.login_redirect'));
    }
}
