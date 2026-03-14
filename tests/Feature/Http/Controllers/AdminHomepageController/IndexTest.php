<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\AdminHomepageController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class IndexTest extends AdminUserTestCase
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

    public function testHomepageReturnsViewForAuthenticatedUser(): void
    {
        $user = $this->createTestUser();

        Gate::define('admin', static fn (): bool => true);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin');
        $response->assertStatus(200);
    }

    public function testHomepageRedirectsGuestToLogin(): void
    {
        $response = $this->get('/admin');
        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }
}
