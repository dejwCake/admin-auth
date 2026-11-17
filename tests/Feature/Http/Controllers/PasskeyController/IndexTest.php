<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\PasskeyController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Database\DatabaseManager;

class IndexTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testDefaultConfigurationOpensThePage(): void
    {
        $this->allowAdmin();

        $this->actingAsVerified($this->createAdmin())
            ->get('/admin/passkeys')
            ->assertOk()
            ->assertViewHas('available', true)
            ->assertSee('<passkey-settings', false)
            ->assertSee('/admin/passkeys/options', false);
    }

    public function testListsOnlyTheAdminsOwnPasskeys(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $this->addPasskey($user, 'YubiKey');
        $other = $this->addPasskey($this->createAdmin('jane@example.com'), 'Jane phone');

        $response = $this->actingAsVerified($user)->get('/admin/passkeys')->assertOk();

        $passkeys = $response->viewData('passkeys');
        self::assertSame(['MacBook', 'YubiKey'], array_column($passkeys, 'name'));
        self::assertNotNull($passkeys[0]['created_at']);
        self::assertNull($passkeys[0]['last_used_at']);
        $response->assertDontSee('Jane phone')
            ->assertDontSee($other->credential_id);
    }

    public function testExposesNoCredentialData(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user);

        $response = $this->actingAsVerified($user)->get('/admin/passkeys');

        self::assertSame(['id', 'name', 'created_at', 'last_used_at'], array_keys($response->viewData('passkeys')[0]));
        $response->assertDontSee($passkey->credential_id);
    }

    public function testAdminWithoutPasskeysGetsAnEmptyList(): void
    {
        $this->allowAdmin();

        $this->actingAsVerified($this->createAdmin())
            ->get('/admin/passkeys')
            ->assertViewHas('passkeys', [])
            ->assertSee(':passkeys="[]"', false);
    }

    public function testMissingTableShowsTheMigrationNotice(): void
    {
        $this->allowAdmin();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->actingAsVerified($this->createAdmin())
            ->get('/admin/passkeys')
            ->assertOk()
            ->assertViewHas('available', false)
            ->assertSee(':available="false"', false);
    }
}
