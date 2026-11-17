<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorChallengeController;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Database\Eloquent\Model;

class ShowAdminUserTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testEnrolledAdminSeesTheChallenge(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->get('/admin/two-factor/challenge')
            ->assertStatus(200)
            ->assertSee('<two-factor-challenge-form', false);
    }

    public function testVerifiedAdminIsSentOn(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->get('/admin/two-factor/challenge')
            ->assertRedirect('/admin');
    }

    public function testAdminWithoutTwoFactorIsSentOn(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor/challenge')
            ->assertRedirect('/admin');
    }

    public function testChallengePageOffersOnlyThePasskeyWithoutTotp(): void
    {
        $this->loginAdminWithPasskey(new VirtualAuthenticator());

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
        $this->get('/admin/two-factor/challenge')
            ->assertOk()
            ->assertSee(':has-totp="false"', false)
            ->assertSee(':has-passkeys="true"', false)
            ->assertSee('/admin/two-factor/challenge/passkey/options', false);
    }

    public function testChallengePageOffersBothWithTotpAndAPasskey(): void
    {
        $this->loginAdminWithPasskey(new VirtualAuthenticator(), true);

        $this->get('/admin/two-factor/challenge')
            ->assertOk()
            ->assertSee(':has-totp="true"', false)
            ->assertSee(':has-passkeys="true"', false);
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
