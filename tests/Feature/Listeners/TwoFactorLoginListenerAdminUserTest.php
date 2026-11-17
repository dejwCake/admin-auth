<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Listeners;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;

/**
 * "Impersonation keeps verification": the impersonal login of the generated admin-users CRUD.
 */
class TwoFactorLoginListenerAdminUserTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testImpersonatingAnAdminWithTwoFactorSkipsTheChallenge(): void
    {
        $admin = $this->verifiedAdmin();
        $target = $this->createAdmin('target@example.com');
        $this->enrol($target);

        $this->get(sprintf('/admin/test-impersonate/%s', $target->getKey()))->assertStatus(200);

        $this->get('/admin')->assertStatus(200);
        self::assertSame(
            $admin->getAuthIdentifier(),
            $this->app['session.store']->get(TwoFactorSession::IMPERSONATED_BY),
        );
        self::assertSame(
            $target->getAuthIdentifier(),
            $this->app->make(AuthFactory::class)->guard($this->adminAuthGuard)->id(),
        );
    }

    public function testImpersonatingAForcedAdminWithoutAFactorSkipsEnrolment(): void
    {
        $this->requireTwoFactor();
        $this->verifiedAdmin();
        $target = $this->createAdmin('target@example.com');

        $this->get(sprintf('/admin/test-impersonate/%s', $target->getKey()))->assertStatus(200);

        $this->get('/admin')->assertStatus(200);
    }

    public function testAdminWithoutAFactorImpersonatingAnAdminWithTwoFactorIsChallenged(): void
    {
        $this->allowAdmin();
        $this->registerImpersonationRoute();
        $admin = $this->createAdmin();
        $target = $this->createAdmin('target@example.com');
        $this->enrol($target);
        // The gate remembers that this admin has nothing to verify; that is not a verification to hand over.
        $this->actingAs($admin, $this->adminAuthGuard)->get('/admin')->assertStatus(200);

        $this->get(sprintf('/admin/test-impersonate/%s', $target->getKey()))->assertStatus(200);

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testPasswordLoginAfterLogoutIsChallenged(): void
    {
        $this->verifiedAdmin();
        $target = $this->createAdmin('target@example.com');
        $this->get(sprintf('/admin/test-impersonate/%s', $target->getKey()))->assertStatus(200);
        $this->get('/admin/logout');

        $other = $this->createAdmin('other@example.com');
        $this->enrol($other);
        $this->post('/admin/login', ['email' => 'other@example.com', 'password' => self::PASSWORD]);

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
        self::assertFalse($this->app['session.store']->has(TwoFactorSession::IMPERSONATED_BY));
    }

    public function testUnverifiedSessionIsNotMarkedVerified(): void
    {
        $this->allowAdmin();
        $this->registerImpersonationRoute();
        $admin = $this->createAdmin();
        $target = $this->createAdmin('target@example.com');
        $this->enrol($target);

        $this->actingAs($admin, $this->adminAuthGuard)
            ->get(sprintf('/admin/test-impersonate/%s', $target->getKey()))
            ->assertStatus(200);

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    /**
     * An enrolled admin whose session passed the challenge.
     */
    private function verifiedAdmin(): Model&TwoFactorAuthenticatable
    {
        $this->allowAdmin();
        $this->registerImpersonationRoute();
        $admin = $this->createAdmin();
        $this->enrol($admin);

        $this->actingAs($admin, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $admin->getAuthIdentifier()]);

        return $admin;
    }

    /**
     * Logs another admin in as the generated impersonalLogin() does.
     */
    private function registerImpersonationRoute(): void
    {
        $guardName = $this->adminAuthGuard;
        $authFactory = $this->app->make(AuthFactory::class);

        $this->app->make(Router::class)
            ->get('/admin/test-impersonate/{id}', static function (string $id) use ($authFactory, $guardName): string {
                $guard = $authFactory->guard($guardName);
                assert($guard instanceof StatefulGuard);
                $guard->login(TestAdminUserModel::query()->findOrFail($id));

                return 'ok';
            })
            ->middleware(['web', sprintf('auth:%s', $guardName), 'admin']);
    }
}
