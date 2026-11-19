<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Testing\TestResponse;

class ShowTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testAdminWithoutFactorsSeesTwoFactorOff(): void
    {
        $this->allowAdmin();

        $response = $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertSee('<two-factor-overview', false)
            ->assertViewHas('required', false);

        $totp = $this->factor($response, 'totp');
        self::assertTrue($totp['available']);
        self::assertFalse($totp['active']);
        self::assertSame('Not set up', $totp['status']);
        self::assertSame('two-factor-settings', $totp['component']);

        $passkey = $this->factor($response, 'passkey');
        self::assertFalse($passkey['active']);
        self::assertSame('None', $passkey['status']);
        self::assertSame(route('brackets/admin-auth::admin/passkeys'), $passkey['url']);
        self::assertSame(['totp', 'passkey'], array_column($this->factors($response), 'key'));
    }

    public function testAdminWithTwoFactorSeesItOnWithoutRecoveryCodes(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $response = $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertDontSee(self::RECOVERY_CODE)
            ->assertDontSee((string) $user->getTwoFactorSecret());

        $totp = $this->factor($response, 'totp');
        self::assertTrue($totp['active']);
        self::assertSame('On', $totp['status']);
        self::assertTrue($totp['props']['enabled']);
    }

    public function testAdminWithPasskeysOnlySeesTwoFactorOnThroughThem(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user);

        $response = $this->actingAsVerified($user)->get('/admin/two-factor')->assertStatus(200);

        $totp = $this->factor($response, 'totp');
        self::assertFalse($totp['active']);
        self::assertSame('Not set up', $totp['status']);
        self::assertFalse($totp['props']['enabled']);

        $passkey = $this->factor($response, 'passkey');
        self::assertTrue($passkey['active']);
        self::assertSame('1 passkey', $passkey['status']);
        self::assertSame('Manage passkeys', $passkey['link_label']);
    }

    public function testForcedAdminMayDisableTotpOnlyWithAPasskey(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);
        $session = [TwoFactorSession::VERIFIED => $user->getAuthIdentifier()];

        $response = $this->actingAs($user, $this->adminAuthGuard)->withSession($session)->get('/admin/two-factor');
        self::assertFalse($this->factor($response, 'totp')['props']['canDisable']);

        $this->addPasskey($user);

        self::assertTrue($this->factor($this->get('/admin/two-factor'), 'totp')['props']['canDisable']);
    }

    public function testForcedAdminWithoutAnyFactorGetsTheEnrolmentNotice(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();

        $response = $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertViewHas('required', true)
            ->assertSee(':required="true"', false)
            ->assertSee('Two-factor authentication is required for your account. Enable it to continue.');

        // The overview shows the notice while required and no factor is active.
        self::assertSame([false, false], array_column($this->factors($response), 'active'));
    }

    public function testForcedAdminWithAPasskeyHasAnActiveFactor(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->addPasskey($user);

        $response = $this->actingAsVerified($user)
            ->get('/admin/two-factor')
            ->assertStatus(200)
            ->assertViewHas('required', true);

        // So the overview shows no enrolment notice.
        self::assertTrue($this->factor($response, 'passkey')['active']);
    }

    public function testMissingColumnsShowTheNoticeInTheAuthenticatorAppSectionOnly(): void
    {
        $this->dropTwoFactorColumns();
        $this->allowAdmin();

        $response = $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertStatus(200);

        $totp = $this->factor($response, 'totp');
        self::assertFalse($totp['available']);
        self::assertSame(
            'Two-factor needs a migration: publish the admin-auth migrations, then run migrate.',
            $totp['notice'],
        );
        self::assertTrue($this->factor($response, 'passkey')['available']);
    }

    public function testPageHasNoPasskeysNote(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor')
            ->assertDontSee('You can also sign in and confirm your login with a passkey');
    }

    /**
     * @return array<int, array<string, array|string|bool|null>>
     */
    private function factors(TestResponse $response): array
    {
        return $response->viewData('factors');
    }

    /**
     * @return array<string, array|string|bool|null>
     */
    private function factor(TestResponse $response, string $key): array
    {
        $factors = array_values(array_filter(
            $this->factors($response),
            static fn (array $factor): bool => $factor['key'] === $key,
        ));
        self::assertCount(1, $factors, sprintf('One "%s" factor', $key));

        return $factors[0];
    }
}
