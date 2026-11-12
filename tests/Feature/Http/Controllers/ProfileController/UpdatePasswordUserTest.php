<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\Models\TestUserModel;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Http\Client\Factory as HttpFactory;
use Override;

class UpdatePasswordUserTest extends UserTestCase
{
    private const string NEW_PASSWORD = 'New-secret-42!';

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        // Password::uncompromised() asks the Have I Been Pwned range API; an empty answer means "not found".
        $this->app->make(HttpFactory::class)->fake(['api.pwnedpasswords.com/*' => '']);
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
    }

    public function testCorrectCurrentPasswordChangesThePassword(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'current_password' => 'testpass123',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['redirect' => route('admin/edit-password')]);
        $stored = $user->fresh()->password;
        self::assertNotSame(self::NEW_PASSWORD, $stored);
        self::assertTrue($this->app->make(Hasher::class)->check(self::NEW_PASSWORD, $stored));
    }

    public function testWrongCurrentPasswordIsRejected(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'current_password' => 'wrong-password',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $response->assertJsonValidationErrors('current_password');
        self::assertTrue($this->app->make(Hasher::class)->check('testpass123', $user->fresh()->password));
    }

    public function testMissingCurrentPasswordIsRejected(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ]);

        $response->assertJsonValidationErrors('current_password');
        self::assertTrue($this->app->make(Hasher::class)->check('testpass123', $user->fresh()->password));
    }

    public function testWeakNewPasswordIsRejected(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'current_password' => 'testpass123',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertJsonValidationErrors('password');
    }

    public function testMismatchedConfirmationIsRejected(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'current_password' => 'testpass123',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => 'Other-secret-42!',
        ]);

        $response->assertJsonValidationErrors('password');
    }

    public function testOtherSessionIsLoggedOutAndThisOneStays(): void
    {
        $user = $this->createTestUser();
        $guard = $this->app->make(AuthFactory::class)->guard($this->adminAuthGuard);
        self::assertInstanceOf(SessionGuard::class, $guard);
        // Session B holds the hash of the password it logged in with, as AuthenticateSession stores it.
        $sessionBHash = $guard->hashPasswordForCookie($user->password);
        $sessionKey = sprintf('password_hash_%s', $this->adminAuthGuard);

        // Session A changes the password ...
        $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/password', [
            'current_password' => 'testpass123',
            'password' => self::NEW_PASSWORD,
            'password_confirmation' => self::NEW_PASSWORD,
        ])->assertStatus(200);

        // ... and stays logged in.
        $this->get('/admin/profile')->assertStatus(200);

        // Session B comes back with the old hash and is sent to the login.
        $this->flushSession();
        $this->withSession([$sessionKey => $sessionBHash])
            ->actingAs($user->fresh(), $this->adminAuthGuard)
            ->get('/admin/profile')
            ->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }

    private function createTestUser(): TestUserModel
    {
        return TestUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
        ]);
    }
}
