<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class EnableTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testStartReturnsTheSecretAndQrCodeWithoutActivating(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $response = $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/two-factor/enable');

        $response->assertStatus(200)->assertJsonStructure(['secret', 'qr_code']);
        self::assertStringStartsWith('<svg', $response->json('qr_code'));
        self::assertSame($response->json('secret'), $user->fresh()->getTwoFactorSecret());
        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
    }

    public function testRestartReplacesAnUnconfirmedSecret(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->actingAs($user, $this->adminAuthGuard);

        $first = $this->postJson('/admin/two-factor/enable')->json('secret');
        $oldCode = $this->currentCode($user->fresh());
        $second = $this->postJson('/admin/two-factor/enable')->json('secret');

        self::assertNotSame($first, $second);
        if ($oldCode !== $this->currentCode($user->fresh())) {
            $this->postJson('/admin/two-factor/confirm', ['code' => $oldCode])->assertJsonValidationErrors('code');
        }
    }

    public function testActiveSetupIsNotReplaced(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $secret = $user->getTwoFactorSecret();

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->postJson('/admin/two-factor/enable')
            ->assertStatus(409);

        self::assertSame($secret, $user->fresh()->getTwoFactorSecret());
    }

    public function testMissingColumnsAreRejected(): void
    {
        $this->dropTwoFactorTable();
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->postJson('/admin/two-factor/enable')
            ->assertStatus(409)
            ->assertJson(['message' => trans('brackets/admin-auth::admin.two_factor.migration_missing')]);
    }
}
