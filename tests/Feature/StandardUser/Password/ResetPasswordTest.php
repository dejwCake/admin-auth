<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\StandardUser\Password;

use Brackets\AdminAuth\Tests\Models\TestStandardUserModel;
use Brackets\AdminAuth\Tests\StandardTestCase;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;

class ResetPasswordTest extends StandardTestCase
{
    use DatabaseMigrations;

    protected string $token = '123456aabbcc';

    public function setUp(): void
    {
        parent::setUp();
    }

    protected function createTestUser(): TestStandardUserModel
    {
        $user = TestStandardUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        $this->assertDatabaseHas('test_standard_user_models', [
            'email' => 'john@example.com',
        ]);

        //create also password reset
        $this->app['db']->connection()->table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => bcrypt($this->token),
            'created_at' => CarbonImmutable::now(),
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'john@example.com',
        ]);

        return $user;
    }

    public function testCanSeeResetPasswordForm(): void
    {
        $response = $this->get(route('brackets/admin-auth::admin/password/show-reset-form', ['token' => $this->token]));
        $response->assertStatus(200);
    }

    public function testResetPasswordAfterFormFilled(): void
    {
        $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token,
            ],
        );
        $response->assertStatus(302);

        $userNew = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertEquals(true, Hash::check('testpass123new', $userNew->password));
    }

    public function testDoNotResetPasswordIfEmailNotFound(): void
    {
        $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john1@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token,
            ],
        );
        $response->assertStatus(302);

        $userNew = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }

    public function testDoNotResetPasswordIfTokenFailed(): void
    {
        $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token . '11',
            ],
        );
        $response->assertStatus(302);

        $userNew = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }

    public function testDoNotResetPasswordIfEmailAndTokenDoesNotMatch(): void
    {
        $this->createTestUser();

        $user2 = TestStandardUserModel::create([
            'email' => 'john2@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        $this->assertDatabaseHas('test_standard_user_models', [
            'email' => 'john2@example.com',
        ]);

        $this->app['db']->connection()->table('password_reset_tokens')->insert([
            'email' => $user2->email,
            'token' => bcrypt($this->token . '2'),
            'created_at' => CarbonImmutable::now(),
        ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'john2@example.com',
        ]);

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john2@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token,
            ],
        );
        $response->assertStatus(302);

        $userNew2 = TestStandardUserModel::where('email', 'john2@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew2->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew2->password));

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token . '2',
            ],
        );
        $response->assertStatus(302);

        $userNew1 = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew1->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew1->password));
    }

    public function testDoNotResetPasswordIfPasswordValidationFailed(): void
    {
        $this->createTestUser();

        //Fixme not working getting error instead of exception
        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass',
                'password_confirmation' => 'testpass',
                'token' => $this->token . '11',
            ],
        );
        $response->assertStatus(302);

        $userNew = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));

        //Fixme not working getting error instead of exception
        // validation for changed password length
        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'test777',
                'password_confirmation' => 'test777',
                'token' => $this->token,
            ],
        );
        $response->assertStatus(302);

        $userNew = TestStandardUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('test777', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }
}
