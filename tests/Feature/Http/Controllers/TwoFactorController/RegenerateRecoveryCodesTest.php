<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class RegenerateRecoveryCodesTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testRegenerateShowsEightNewCodesAndInvalidatesTheOldOnes(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $response = $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->postJson('/admin/two-factor/recovery-codes', ['current_password' => self::PASSWORD]);

        $response->assertStatus(200);
        self::assertCount(8, $response->json('recovery_codes'));
        self::assertCount(8, array_unique($response->json('recovery_codes')));

        // As in a new session: the old code is rejected at the challenge, a new one is accepted.
        $this->app['session.store']->forget(TwoFactorSession::VERIFIED);
        $this->postJson('/admin/two-factor/challenge', ['recovery_code' => self::RECOVERY_CODE])
            ->assertJsonValidationErrors('recovery_code');
        $this->postJson('/admin/two-factor/challenge', ['recovery_code' => $response->json('recovery_codes.0')])
            ->assertStatus(200);
    }

    public function testWrongOrMissingPasswordChangesNothing(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);
        $codes = $user->fresh()->getTwoFactorRecoveryCodes();
        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()]);

        $this->postJson('/admin/two-factor/recovery-codes', ['current_password' => 'wrong'])
            ->assertJsonValidationErrors('current_password');
        $this->postJson('/admin/two-factor/recovery-codes', [])
            ->assertJsonValidationErrors('current_password');

        self::assertSame($codes, $user->fresh()->getTwoFactorRecoveryCodes());
    }
}
