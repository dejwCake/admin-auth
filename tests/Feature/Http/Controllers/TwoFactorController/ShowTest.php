<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class ShowTest extends AdminUserTestCase
{
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
