<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorChallengeController;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorChallenge;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Database\Eloquent\Model;

class PasskeyAdminUserTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testPasskeyRedirectsToTheRequestedPage(): void
    {
        $authenticator = new VirtualAuthenticator();
        $user = $this->loginAdminWithPasskey($authenticator);
        // The gate keeps the page the admin asked for as the intended URL.
        $this->get('/admin/profile')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));

        $options = $this->postJson('/admin/two-factor/challenge/passkey/options')->assertOk()->json();
        $this->postJson('/admin/two-factor/challenge/passkey', [
            'credential' => $this->assertion($authenticator, $options, $user),
        ])->assertOk()->assertJson(['redirect' => url('/admin/profile')]);

        self::assertSame($user->getAuthIdentifier(), $this->passkeySession()->get(TwoFactorSession::VERIFIED));
        $this->get('/admin')->assertOk();
    }

    public function testCodeOfAnUnconfirmedSecretIsRejected(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook', new VirtualAuthenticator());
        // An abandoned authenticator enrolment: secret stored, never confirmed.
        $this->storeUnconfirmedSecret($user);
        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $this->postJson('/admin/two-factor/challenge', ['code' => $this->currentCode($user)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testAnotherAdminsPasskeyIsRejected(): void
    {
        $this->loginAdminWithPasskey(new VirtualAuthenticator());
        $jane = $this->createAdmin('jane@example.com');
        $janes = new VirtualAuthenticator();
        $this->addPasskey($jane, 'Phone', $janes);

        $options = $this->postJson('/admin/two-factor/challenge/passkey/options')->json();
        $this->postJson('/admin/two-factor/challenge/passkey', [
            'credential' => $this->assertion($janes, $options, $jane),
        ])->assertUnprocessable()->assertJsonValidationErrors('passkey');

        self::assertNull($this->passkeySession()->get(TwoFactorSession::VERIFIED));
        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testFailedCodesAndPasskeysShareTheLimit(): void
    {
        $authenticator = new VirtualAuthenticator();
        $user = $this->loginAdminWithPasskey($authenticator, true);
        for ($i = 0; $i < TwoFactorChallenge::MAX_ATTEMPTS; $i++) {
            $this->postJson('/admin/two-factor/challenge', ['code' => $this->wrongCode($user)])->assertUnprocessable();
        }

        $options = $this->postJson('/admin/two-factor/challenge/passkey/options')->json();
        $this->postJson('/admin/two-factor/challenge/passkey', [
            'credential' => $this->assertion($authenticator, $options, $user),
        ])->assertTooManyRequests();

        self::assertNull($this->passkeySession()->get(TwoFactorSession::VERIFIED));
    }

    /**
     * An admin with the passkey, logged in with their password and not verified yet.
     */
    private function loginAdminWithPasskey(
        VirtualAuthenticator $authenticator,
        bool $withTotp = false,
    ): Model&TwoFactorAuthenticatable&PasskeyAuthenticatable {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook', $authenticator);
        // Before the login: the guard keeps the admin it loaded for the rest of the test.
        if ($withTotp) {
            $this->enrol($user);
        }
        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        return $user;
    }
}
