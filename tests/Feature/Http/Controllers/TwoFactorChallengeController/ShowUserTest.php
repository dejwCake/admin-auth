<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorChallengeController;

use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;

class ShowUserTest extends UserTestCase
{
    use TwoFactorHelpers;

    public function testEnrolledAdminSeesTheChallenge(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->get('/admin/two-factor/challenge')
            ->assertStatus(200)
            ->assertSee('<two-factor-challenge-form', false);
    }

    public function testVerifiedAdminIsSentOn(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()])
            ->get('/admin/two-factor/challenge')
            ->assertRedirect('/admin');
    }

    public function testAdminWithoutTwoFactorIsSentOn(): void
    {
        $this->allowAdmin();

        $this->actingAs($this->createAdmin(), $this->adminAuthGuard)
            ->get('/admin/two-factor/challenge')
            ->assertRedirect('/admin');
    }
}
