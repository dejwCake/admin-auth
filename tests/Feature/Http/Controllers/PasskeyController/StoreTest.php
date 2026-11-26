<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\PasskeyController;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;

class StoreTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testAddAPasskey(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $response = $this->add($user, new VirtualAuthenticator(), 'MacBook');

        $response->assertCreated()
            ->assertJsonPath('passkey.name', 'MacBook')
            ->assertJsonMissingPath('passkey.credential');
        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
        $this->get('/admin/passkeys')->assertSee('MacBook');
    }

    public function testAddingAPasskeyDropsAnUnconfirmedAuthenticatorSecret(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        // An abandoned authenticator enrolment: secret stored, never confirmed.
        $this->storeUnconfirmedSecret($user);

        $this->add($user, new VirtualAuthenticator(), 'MacBook')->assertCreated();

        self::assertNull($user->fresh()?->getTwoFactorSecret());
    }

    public function testNameIsRequiredAndAtMost255Characters(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $this->add($user, new VirtualAuthenticator(), '')->assertJsonValidationErrors('name');
        $this->add($user, new VirtualAuthenticator(), str_repeat('a', 256))->assertJsonValidationErrors('name');

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testResponseWithoutUserVerificationIsRejected(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $this->add($user, new VirtualAuthenticator(), 'MacBook', false)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testReusedChallengeIsRejected(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $options = $this->actingAsVerified($user)
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])
            ->json();
        $this->postJson('/admin/passkeys', [
            'name' => 'MacBook',
            'credential' => (new VirtualAuthenticator())->register($options, $this->origin()),
        ])->assertCreated();

        $this->postJson('/admin/passkeys', [
            'name' => 'YubiKey',
            'credential' => (new VirtualAuthenticator())->register($options, $this->origin()),
        ])->assertUnprocessable()->assertJsonValidationErrors('passkey');

        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
    }

    public function testExpiredChallengeIsRejected(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $options = $this->actingAsVerified($user)
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])
            ->json();
        $this->travel(6)->minutes();

        $this->postJson('/admin/passkeys', [
            'name' => 'MacBook',
            'credential' => (new VirtualAuthenticator())->register($options, $this->origin()),
        ])->assertUnprocessable()->assertJsonValidationErrors('passkey');

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testSameAuthenticatorTwiceIsRejected(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $existing = $this->addPasskey($user, 'MacBook', $authenticator);

        $this->add($user, $authenticator, 'Again')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');

        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
        self::assertEquals($existing->credential, $existing->fresh()?->credential);
    }

    private function add(
        Model&TwoFactorAuthenticatable&PasskeyAuthenticatable $user,
        VirtualAuthenticator $authenticator,
        string $name,
        bool $userVerified = true,
    ): TestResponse {
        $options = $this->actingAsVerified($user)
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])
            ->json();

        return $this->postJson('/admin/passkeys', [
            'name' => $name,
            'credential' => $authenticator->register($options, $this->origin(), $userVerified),
        ]);
    }
}
