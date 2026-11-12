<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;

class UpdateProfileUserTest extends UserTestCase
{
    public function testEmailChangeWithCorrectPasswordIsSaved(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'new@example.com',
            'current_password' => 'testpass123',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('test_standard_user_models', [
            'id' => $user->getKey(),
            'email' => 'new@example.com',
        ]);
    }

    public function testEmailChangeWithoutPasswordIsRejected(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'new@example.com',
        ]);

        $response->assertJsonValidationErrors('current_password');
        $this->assertDatabaseHas('test_standard_user_models', [
            'id' => $user->getKey(),
            'email' => 'john@example.com',
        ]);
    }

    public function testEmailTakenByAnotherUserIsRejected(): void
    {
        $user = $this->createTestUser();
        TestUserModel::create([
            'email' => 'taken@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
        ]);

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'taken@example.com',
            'current_password' => 'testpass123',
        ]);

        $response->assertJsonValidationErrors('email');
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
