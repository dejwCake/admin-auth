<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

class ConfirmTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testValidCodeActivatesAndVerifiesTheSession(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/two-factor/enable');

        $response = $this->postJson('/admin/two-factor/confirm', ['code' => $this->currentCode($user->fresh())]);

        $response->assertStatus(200);
        self::assertCount(8, $response->json('recovery_codes'));
        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->get('/admin')->assertStatus(200);
    }

    public function testInvalidCodeKeepsTwoFactorInactive(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->actingAs($user, $this->adminAuthGuard)->postJson('/admin/two-factor/enable');

        $this->postJson('/admin/two-factor/confirm', ['code' => $this->wrongCode($user->fresh())])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        self::assertFalse($user->fresh()->hasTwoFactorEnabled());
    }
}
