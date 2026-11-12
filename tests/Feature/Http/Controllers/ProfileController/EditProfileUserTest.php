<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;

class EditProfileUserTest extends UserTestCase
{
    public function testUserOpensTheProfilePage(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertStatus(200);
        $response->assertSee('<profile-form', false);
        $response->assertSee('john@example.com');
    }

    public function testGuestIsRedirectedToTheLogin(): void
    {
        $response = $this->get('/admin/profile');

        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }

    private function createTestUser(): TestUserModel
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);

        return TestUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
        ]);
    }
}
