<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Middleware;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class CanAdminTest extends AdminUserTestCase
{
    protected function createTestUser(bool $activated = true, bool $forbidden = false): TestAdminUserModel
    {
        Notification::fake();

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => $activated,
            'forbidden' => $forbidden,
        ]);
    }

    public function testAuthenticatedAdminCanAccessProtectedRoute(): void
    {
        $user = $this->createTestUser();

        Gate::define('admin', static fn (): bool => true);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin');
        $response->assertStatus(200);
    }

    public function testUnauthenticatedUserIsRedirectedToLogin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }

    public function testAuthenticatedUserWithoutAdminPermissionGets403(): void
    {
        $user = $this->createTestUser();

        Gate::define('admin', static fn (): bool => false);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin');
        $response->assertStatus(500);
    }
}
