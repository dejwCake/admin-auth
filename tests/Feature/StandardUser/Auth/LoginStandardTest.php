<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\StandardUser\Auth;

use Brackets\AdminAuth\Tests\Models\TestStandardUserModel;
use Brackets\AdminAuth\Tests\StandardTestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;

class LoginStandardTest extends StandardTestCase
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

    public function testLoginPageIsAccessible(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }

    public function testUserCanLogIn(): void
    {
        $this->createTestUser();

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertNotEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testUserWithWrongCredentialsCannotLogIn(): void
    {
        $this->createTestUser();

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'incorrect password']);
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
