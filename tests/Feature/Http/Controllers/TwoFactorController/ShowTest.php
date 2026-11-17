<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class ShowTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testDefaultConfigurationOpensThePageWithTwoFactorOff(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertViewHas('available', true)
            ->assertViewHas('enabled', false)
            ->assertViewHas('required', false)
            ->assertSee(':enabled="false"', false);
    }

    public function testAdminWithTwoFactorSeesItOnWithoutRecoveryCodes(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertSee(':enabled="true"', false)
            ->assertDontSee(self::RECOVERY_CODE)
            ->assertDontSee((string) $user->getTwoFactorSecret());
    }

    public function testForcedAdminMayDisableTotpOnlyWithAPasskey(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);
        $session = [TwoFactorSession::VERIFIED => $user->getAuthIdentifier()];

        $this->actingAs($user, $this->adminAuthGuard)->withSession($session)
            ->get('/admin/two-factor')
            ->assertViewHas('canDisable', false)
            ->assertSee(':can-disable="false"', false);

        $this->addPasskey($user);

        $this->get('/admin/two-factor')
            ->assertViewHas('canDisable', true)
            ->assertSee(':can-disable="true"', false);
    }

    public function testPageLinksToThePasskeys(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertSee(route('brackets/admin-auth::admin/passkeys'), false);
    }

    public function testMissingColumnsShowTheMigrationNotice(): void
    {
        $this->dropTwoFactorColumns();
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertSee('two-factor-migration-missing', false)
            ->assertDontSee('<two-factor-settings', false);
    }
}
