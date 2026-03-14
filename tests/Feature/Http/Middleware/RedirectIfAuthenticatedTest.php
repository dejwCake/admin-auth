<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Middleware;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Notification;

class RedirectIfAuthenticatedTest extends AdminUserTestCase
{
    protected function createTestUser(): TestAdminUserModel
    {
        Notification::fake();

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);
    }

    public function testAuthenticatedAdminIsRedirectedFromGuestRoute(): void
    {
        $user = $this->createTestUser();

        $this->actingAs($user, $this->adminAuthGuard);

        $response = $this->get('/admin/login');
        $response->assertStatus(302);
        $response->assertRedirect($this->app['config']->get('admin-auth.login_redirect'));
    }

    public function testGuestCanAccessGuestRoute(): void
    {
        $response = $this->get('/admin/login');
        $response->assertStatus(200);
    }
}
