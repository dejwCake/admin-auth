<?php

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Auth;

use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Auth;

class LogoutTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected function createTestUser(): TestBracketsUserModel
    {
        $user = TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john@example.com',
            'activated' => true,
            'forbidden' => false,
        ]);

        return $user;
    }

    public function testAuthUserCanLogout(): void
    {
        $user = $this->createTestUser();

        $response = $this->post('/admin/login', ['email' => 'john@example.com', 'password' => 'testpass123']);
        $response->assertStatus(302);

        self::assertNotEmpty(Auth::guard($this->adminAuthGuard)->user());

        $response = $this->get('/admin/logout');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }

    public function testNotAuthUserCannotLogout(): void
    {
        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());

        $response = $this->get('/admin/logout');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        self::assertEmpty(Auth::guard($this->adminAuthGuard)->user());
    }
}
