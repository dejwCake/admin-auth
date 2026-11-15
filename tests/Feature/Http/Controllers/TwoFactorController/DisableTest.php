<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class DisableTest extends AdminUserTestCase
{
    use TwoFactorHelpers;

    public function testDisableRemovesTheDataAndTheNextLoginNeedsNoCode(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->deleteJson('/admin/two-factor', ['current_password' => self::PASSWORD])
            ->assertStatus(200);

        self::assertFalse($user->fresh()->hasTwoFactorData());

        $this->get('/admin/logout');
        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);
        $this->get('/admin')->assertStatus(200);
    }

    public function testWrongPasswordChangesNothing(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->deleteJson('/admin/two-factor', ['current_password' => 'wrong'])
            ->assertJsonValidationErrors('current_password');

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
    }

    public function testDisableWhileRequiredIsRejected(): void
    {
        $this->allowAdmin();
        $this->requireTwoFactor();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->deleteJson('/admin/two-factor', ['current_password' => self::PASSWORD])
            ->assertStatus(409);

        self::assertTrue($user->fresh()->hasTwoFactorEnabled());
    }
}
