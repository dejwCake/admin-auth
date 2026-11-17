<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\PasskeyLoginController;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Passkeys\PasskeyLogin;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\TestResponse;

class LoginUserTest extends UserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testSignInWithAPasskeySkipsTheChallenge(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $this->signIn($authenticator, $user)->assertNoContent();

        self::assertSame($user->getAuthIdentifier(), $this->loggedInUser()?->getAuthIdentifier());
        $this->get('/admin')->assertOk();
    }

    public function testForcedAdminWithOnlyAPasskeySignsIn(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $this->signIn($authenticator, $user)->assertNoContent();

        $this->get('/admin')->assertOk();
    }

    public function testUnknownPasskeyFails(): void
    {
        $user = $this->createAdmin();

        $this->signIn(new VirtualAuthenticator(), $user)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');

        self::assertNull($this->loggedInUser());
    }

    public function testResponseWithoutUserVerificationFails(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $this->signIn($authenticator, $user, false)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');

        self::assertNull($this->loggedInUser());
    }

    public function testAcceptedResponseCannotBeReplayed(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->postJson('/admin/login/passkey/options')->json();
        $credential = $this->assertion($authenticator, $options, $user);
        $this->postJson('/admin/login/passkey', ['credential' => $credential])->assertNoContent();
        $this->post('/admin/logout');

        $this->postJson('/admin/login/passkey', ['credential' => $credential])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');

        self::assertNull($this->loggedInUser());
    }

    public function testSignInRecordsTheLastUse(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $passkey = $this->addPasskey($user, 'MacBook', $authenticator);

        $this->signIn($authenticator, $user)->assertNoContent();

        self::assertNotNull($passkey->fresh()?->last_used_at);
    }

    public function testRememberMeApplies(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $this->signIn($authenticator, $user, remember: true)->assertNoContent();

        self::assertNotNull($user->fresh()?->getRememberToken());
    }

    public function testTooManyFailedAttemptsAreThrottledPerIp(): void
    {
        $user = $this->createAdmin();
        for ($i = 0; $i < PasskeyLogin::MAX_ATTEMPTS; $i++) {
            $this->signIn(new VirtualAuthenticator(), $user)->assertUnprocessable();
        }
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $response = $this->signIn($authenticator, $user);

        $response->assertTooManyRequests();
        self::assertStringContainsString('seconds', (string) $response->json('errors.passkey.0'));
        self::assertNull($this->loggedInUser());
    }

    private function signIn(
        VirtualAuthenticator $authenticator,
        Model&TwoFactorAuthenticatable&PasskeyAuthenticatable $user,
        bool $userVerified = true,
        bool $remember = false,
    ): TestResponse {
        $options = $this->postJson('/admin/login/passkey/options')->json();

        return $this->postJson('/admin/login/passkey', [
            'credential' => $this->assertion($authenticator, $options, $user, $userVerified),
            'remember' => $remember,
        ]);
    }

    private function loggedInUser(): mixed
    {
        return $this->app->make(AuthFactory::class)->guard($this->adminAuthGuard)->user();
    }
}
