<?php

namespace Brackets\AdminAuth\Tests\Feature\AdminUser\Password;

use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Tests\BracketsTestCase;
use Brackets\AdminAuth\Tests\Models\TestBracketsUserModel;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class ResetPasswordTest extends BracketsTestCase
{
    use DatabaseMigrations;

    protected $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->token = '123456aabbcc';
    }

    protected function createTestUser(): TestBracketsUserModel
    {
        Notification::fake();
        $user = TestBracketsUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
        ]);
        Notification::assertSentTo(
            $user,
            ActivationNotification::class
        );

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john@example.com',
        ]);

        //create also password reset
        $this->app['db']->connection()->table('admin_password_resets')->insert([
            'email' => $user->email,
            'token' => bcrypt($this->token),
            'created_at' => Carbon::now()
        ]);

        $this->assertDatabaseHas('admin_password_resets', [
            'email' => 'john@example.com',
        ]);

        return $user;
    }

    public function testCanSeeResetPasswordForm(): void
    {
        $response = $this->get(route('brackets/admin-auth::admin/password/showResetForm', ['token' => $this->token]));
        $response->assertStatus(200);
    }

    public function testResetPasswordAfterFormFilled(): void
    {
        Notification::fake();
        $user = $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token
            ]
        );
        $response->assertStatus(302);

        Notification::assertSentTo(
            $user,
            ActivationNotification::class
        );

        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertEquals(true, Hash::check('testpass123new', $userNew->password));
    }

    public function testDoNotResetPasswordIfEmailNotFound(): void
    {
        $user = $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john1@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token
            ]
        );
        $response->assertStatus(302);

        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }

    public function testDoNotResetPasswordIfTokenFailed(): void
    {
        $user = $this->createTestUser();

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token . '11'
            ]
        );
        $response->assertStatus(302);

        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }

    public function testDoNotResetPasswordIfEmailAndTokenDoesNotMatch(): void
    {
        $user1 = $this->createTestUser();

        $user2 = TestBracketsUserModel::create([
            'email' => 'john2@example.com',
            'password' => bcrypt('testpass123'),
        ]);

        $this->assertDatabaseHas('test_brackets_user_models', [
            'email' => 'john2@example.com',
        ]);

        //TODO create also password reset
        $this->app['db']->connection()->table('password_reset_tokens')->insert([
            'email' => $user2->email,
            'token' => bcrypt($this->token . '2'),
            'created_at' => Carbon::now()
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
                'token' => $this->token
            ]
        );
        $response->assertStatus(302);

        $userNew2 = TestBracketsUserModel::where('email', 'john2@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew2->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew2->password));

        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass123new',
                'password_confirmation' => 'testpass123new',
                'token' => $this->token . '2'
            ]
        );
        $response->assertStatus(302);

        $userNew1 = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass123new', $userNew1->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew1->password));
    }

    public function testDoNotResetPasswordIfPasswordValidationFailed(): void
    {
        $user = $this->createTestUser();

        //Fixme not working getting error instead of exception
        $response = $this->post(
            url('/admin/password-reset/reset'),
            [
                'email' => 'john@example.com',
                'password' => 'testpass',
                'password_confirmation' => 'testpass',
                'token' => $this->token . '11'
            ]
        );
        $response->assertStatus(302);

        $userNew = TestBracketsUserModel::where('email', 'john@example.com')->first();

        self::assertNotEquals(true, Hash::check('testpass', $userNew->password));
        self::assertEquals(true, Hash::check('testpass123', $userNew->password));
    }
}
