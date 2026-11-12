<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;

class UpdateProfileAdminUserTest extends AdminUserTestCase
{
    public function testValidUpdateIsSaved(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'first_name' => 'Johnny',
            'last_name' => 'Smith',
            'email' => 'john@example.com',
            'language' => 'sk',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['redirect' => route('admin/edit-profile')]);
        self::assertNotEmpty($response->json('message'));
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'first_name' => 'Johnny',
            'last_name' => 'Smith',
            'language' => 'sk',
        ]);
    }

    public function testEmailTakenByAnotherAdminIsRejected(): void
    {
        $user = $this->createTestUser('john@example.com');
        $this->createTestUser('taken@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'first_name' => 'Johnny',
            'email' => 'taken@example.com',
            'current_password' => 'testpass123',
        ]);

        $response->assertJsonValidationErrors('email');
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function testEmailOfSoftDeletedAdminCanBeUsed(): void
    {
        $user = $this->createTestUser('john@example.com');
        $this->createTestUser('deleted@example.com')->delete();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'deleted@example.com',
            'current_password' => 'testpass123',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'email' => 'deleted@example.com',
        ]);
    }

    public function testEmailChangeWithCorrectPasswordIsSaved(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'new@example.com',
            'current_password' => 'testpass123',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'email' => 'new@example.com',
        ]);
    }

    public function testEmailChangeWithoutPasswordIsRejected(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'first_name' => 'Johnny',
            'email' => 'new@example.com',
        ]);

        $response->assertJsonValidationErrors('current_password');
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'first_name' => 'John',
            'email' => 'john@example.com',
        ]);
    }

    public function testEmailChangeWithWrongPasswordIsRejected(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'email' => 'new@example.com',
            'current_password' => 'wrong-password',
        ]);

        $response->assertJsonValidationErrors('current_password');
        $this->assertDatabaseHas('test_brackets_user_models', [
            'id' => $user->getKey(),
            'email' => 'john@example.com',
        ]);
    }

    public function testUnchangedEmailNeedsNoPassword(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/profile', [
            'first_name' => 'Johnny',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertJsonMissingValidationErrors('current_password');
    }

    public function testNonAjaxUpdateRedirectsToTheProfilePage(): void
    {
        $user = $this->createTestUser('john@example.com');

        $response = $this->actingAs($user, $this->adminAuthGuard)->post('/admin/profile', [
            'first_name' => 'Johnny',
        ]);

        $response->assertRedirect(route('admin/edit-profile'));
    }

    private function createTestUser(string $email): TestAdminUserModel
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);

        return TestAdminUserModel::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
            'language' => 'en',
        ]);
    }
}
