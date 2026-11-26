<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\PasskeyController;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use PHPUnit\Framework\Attributes\DataProvider;

class DestroyTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testRemovedPasskeyCanNoLongerSignIn(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $passkey = $this->addPasskey($user, 'MacBook', $authenticator);

        $this->actingAsVerified($user)
            ->deleteJson(sprintf('/admin/passkeys/%d', $passkey->id), ['current_password' => self::PASSWORD])
            ->assertOk();

        self::assertNull(AdminPasskey::query()->find($passkey->id));
        $this->post('/admin/logout');
        $options = $this->postJson('/admin/login/passkey/options')->json();
        $this->postJson('/admin/login/passkey', ['credential' => $this->assertion($authenticator, $options, $user)])
            ->assertUnprocessable();
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideWrongPasswords(): iterable
    {
        yield 'wrong' => [['current_password' => 'wrong-password']];
        yield 'missing' => [[]];
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('provideWrongPasswords')]
    public function testWrongPasswordKeepsThePasskey(array $data): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user);

        $this->actingAsVerified($user)
            ->deleteJson(sprintf('/admin/passkeys/%d', $passkey->id), $data)
            ->assertJsonValidationErrors('current_password');

        self::assertNotNull($passkey->fresh());
    }

    public function testLastFactorOfAForcedAdminStays(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user);

        $this->actingAsVerified($user)
            ->deleteJson(sprintf('/admin/passkeys/%d', $passkey->id), ['current_password' => self::PASSWORD])
            ->assertConflict();

        self::assertNotNull($passkey->fresh());
    }

    public function testForcedAdminWithTotpMayRemoveIt(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);
        $passkey = $this->addPasskey($user);

        $this->actingAsVerified($user)
            ->deleteJson(sprintf('/admin/passkeys/%d', $passkey->id), ['current_password' => self::PASSWORD])
            ->assertOk();

        self::assertNull($passkey->fresh());
    }

    public function testAnotherAdminsPasskeyIsNotFound(): void
    {
        $this->allowAdmin();
        $other = $this->addPasskey($this->createAdmin('jane@example.com'));
        $user = $this->createAdmin();

        $this->actingAsVerified($user)
            ->deleteJson(sprintf('/admin/passkeys/%d', $other->id), ['current_password' => self::PASSWORD])
            ->assertNotFound();

        self::assertNotNull($other->fresh());
    }

    public function testNonNumericIdIsNotFound(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        // Not a database error on PostgreSQL, whose id column is an integer.
        $this->actingAsVerified($user)->deleteJson(
            '/admin/passkeys/abc',
            ['current_password' => self::PASSWORD],
        )->assertNotFound();
    }
}
