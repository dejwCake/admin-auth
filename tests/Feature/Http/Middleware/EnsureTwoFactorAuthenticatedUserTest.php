<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Middleware;

use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;

class EnsureTwoFactorAuthenticatedUserTest extends UserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testPasswordLoginOfEnrolledAdminIsChallenged(): void
    {
        $this->allowAdmin();
        $this->enrol($this->createAdmin());

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testSettingsPageIsLockedBeforeVerification(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $challenge = route('brackets/admin-auth::admin/two-factor/challenge');

        $this->actingAs($user, $this->adminAuthGuard)->get('/admin/two-factor')->assertRedirect($challenge);
        $this->delete('/admin/two-factor', ['current_password' => self::PASSWORD])->assertRedirect($challenge);

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function testJsonRequestBeforeVerificationGets403WithTheChallengeUrl(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->getJson('/admin')
            ->assertStatus(403)
            ->assertJson(['redirect' => route('brackets/admin-auth::admin/two-factor/challenge')]);
    }

    public function testRememberMeLoginIsChallenged(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $user->setRememberToken(Str::random(60));
        $user->save();
        $guard = $this->app->make(AuthFactory::class)->guard($this->adminAuthGuard);
        self::assertInstanceOf(SessionGuard::class, $guard);

        $this->withCookie(
            $guard->getRecallerName(),
            sprintf(
                '%s|%s|%s',
                $user->getAuthIdentifier(),
                $user->getRememberToken(),
                $guard->hashPasswordForCookie($user->getAuthPassword()),
            ),
        )->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));

        self::assertTrue($guard->viaRemember());
    }

    public function testSessionVerifiedForAnotherAdminIsChallenged(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $other = $this->createAdmin('jane@example.com');

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $other->getAuthIdentifier()])
            ->get('/admin')
            ->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testAdminWithoutTwoFactorIsNotChallenged(): void
    {
        $this->allowAdmin();
        $this->createAdmin();

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $this->get('/admin')->assertStatus(200);
    }

    public function testRequiredForEveryoneAllowsOnlyEnrolment(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $settings = route('brackets/admin-auth::admin/two-factor');

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)->get('/admin')->assertRedirect($settings);
        $this->get('/admin/profile')->assertRedirect($settings);
        $this->get('/admin/two-factor')->assertStatus(200);
        $this->postJson('/admin/two-factor/enable')->assertStatus(200);
    }

    public function testEnrolmentEndsTheRedirect(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();

        $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/two-factor/enable')->assertStatus(200);
        $this->postJson('/admin/two-factor/confirm', ['code' => $this->currentCode($user->fresh())])
            ->assertStatus(200);

        $this->get('/admin')->assertStatus(200);
    }

    public function testMissingColumnsLeaveAdminPagesOpen(): void
    {
        $this->dropTwoFactorColumns();
        $this->allowAdmin();
        $this->createAdmin();

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $this->get('/admin')->assertStatus(200);
    }

    public function testMissingColumnsWithRequiredTwoFactorRedirectToTheNotice(): void
    {
        $this->dropTwoFactorColumns();
        $this->allowAdmin();
        $this->requireTwoFactor();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin')
            ->assertRedirect(route('brackets/admin-auth::admin/two-factor'));
        $this->get('/admin/two-factor')->assertSee('Two-factor needs a migration');
    }

    public function testPasswordLoginOfAdminWithOnlyAPasskeyIsChallenged(): void
    {
        $this->allowAdmin();
        $this->addPasskey($this->createAdmin());

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testChallengePasskeyRoutesAreReachableBeforeVerification(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->postJson('/admin/two-factor/challenge/passkey/options')
            ->assertOk();
    }

    public function testRequiredForEveryoneAllowsThePasskeysPage(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $settings = route('brackets/admin-auth::admin/two-factor');

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)->get('/admin')->assertRedirect($settings);
        $this->get('/admin/passkeys')->assertOk();
        $this->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])->assertOk();
    }

    public function testForcedAdminWithAPasskeyOnlyPassesTheChallengeWithIt(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        $options = $this->postJson('/admin/two-factor/challenge/passkey/options')->json();
        $this->postJson('/admin/two-factor/challenge/passkey', [
            'credential' => $this->assertion($authenticator, $options, $user),
        ])->assertOk();

        $this->get('/admin')->assertOk();
    }

    public function testForcedAdminEnrolsWithAPasskey(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->actingAs($user, $this->adminAuthGuard)->get('/admin')
            ->assertRedirect(route('brackets/admin-auth::admin/two-factor'));

        $options = $this->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])->json();
        $this->postJson('/admin/passkeys', [
            'name' => 'MacBook',
            'credential' => (new VirtualAuthenticator())->register($options, $this->origin()),
        ])->assertCreated();

        $this->get('/admin')->assertOk();
    }

    public function testAdminWithoutAFactorIsRememberedAsNothingToVerify(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $this->actingAs($user, $this->adminAuthGuard)->get('/admin')->assertOk();

        self::assertSame($user->getAuthIdentifier(), $this->passkeySession()->get(TwoFactorSession::UNENROLLED));
        self::assertNull($this->passkeySession()->get(TwoFactorSession::VERIFIED));
    }

    public function testForcedAdminIsNotRememberedAsNothingToVerify(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)->get('/admin');

        self::assertNull($this->passkeySession()->get(TwoFactorSession::UNENROLLED));
    }

    public function testMissingPasskeysTableMeansNoPasskeys(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user);
        $this->app->make(DatabaseManager::class)
            ->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->actingAs($user, $this->adminAuthGuard)->get('/admin')->assertOk();
    }
}
