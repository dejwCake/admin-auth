<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Middleware;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Str;

class EnsureTwoFactorAuthenticatedAdminUserTest extends AdminUserTestCase
{
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
        $this->get('/admin/two-factor')->assertSee('two-factor-migration-missing', false);
    }

    public function testRequiredForARoleTheAdminHas(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor(['Administrator']);

        $this->actingAs($this->createRoleAdmin(['Administrator']), $this->adminAuthGuard)
            ->get('/admin')
            ->assertRedirect(route('brackets/admin-auth::admin/two-factor'));
    }

    public function testNotRequiredForARoleTheAdminDoesNotHave(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor(['Administrator']);

        $this->actingAs($this->createRoleAdmin(['Editor']), $this->adminAuthGuard)
            ->get('/admin')
            ->assertStatus(200);
    }
}
