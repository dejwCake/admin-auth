<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;

class EditPasswordUserTest extends UserTestCase
{
    public function testUserOpensThePasswordPage(): void
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
        $user = TestUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
        ]);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/password');

        $response->assertStatus(200);
        $response->assertSee('<password-change-form', false);
    }

    public function testGuestIsRedirectedToTheLogin(): void
    {
        $response = $this->get('/admin/password');

        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }
}
