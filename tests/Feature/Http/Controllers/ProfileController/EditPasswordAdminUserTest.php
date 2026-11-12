<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;

class EditPasswordAdminUserTest extends AdminUserTestCase
{
    public function testAdminOpensThePasswordPage(): void
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/password');

        $response->assertStatus(200);
        $response->assertSee('<password-change-form', false);
        $response->assertDontSee('brackets/admin-auth::');
    }

    public function testGuestIsRedirectedToTheLogin(): void
    {
        $response = $this->get('/admin/password');

        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }
}
