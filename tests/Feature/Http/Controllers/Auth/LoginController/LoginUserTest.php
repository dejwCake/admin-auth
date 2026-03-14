<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\LoginController;

use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Support\Facades\Auth;

class LoginUserTest extends UserTestCase
{
    protected function createTestUser(): TestUserModel
    {
        $user = TestUserModel::create([
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
