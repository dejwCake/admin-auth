<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorChallengeController;

use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Database\Eloquent\Model;

class VerifyUserTest extends UserTestCase
{
    use TwoFactorHelpers;

    public function testValidCodeRedirectsToTheRequestedPage(): void
    {
        $user = $this->loginEnrolledAdmin();
        // The gate keeps the page the admin asked for as the intended URL.
        $this->get('/admin/profile')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));

        $this->post('/admin/two-factor/challenge', ['code' => $this->currentCode($user)])
            ->assertRedirect(url('/admin/profile'));

        $this->get('/admin')->assertStatus(200);
    }

    public function testValidCodeAnswersJsonWithTheRedirect(): void
    {
        $user = $this->loginEnrolledAdmin();

        $this->postJson('/admin/two-factor/challenge', ['code' => $this->currentCode($user)])
            ->assertStatus(200)
            ->assertJson(['redirect' => '/admin']);

        self::assertSame(
            $user->getAuthIdentifier(),
            $this->app['session.store']->get(TwoFactorSession::VERIFIED),
        );
    }

    public function testRecoveryCodeVerifiesOnce(): void
    {
        $this->loginEnrolledAdmin();

        $this->postJson('/admin/two-factor/challenge', ['recovery_code' => self::RECOVERY_CODE])->assertStatus(200);
        $this->get('/admin')->assertStatus(200);

        $this->app['session.store']->forget(TwoFactorSession::VERIFIED);
        $this->postJson('/admin/two-factor/challenge', ['recovery_code' => self::RECOVERY_CODE])
            ->assertJsonValidationErrors('recovery_code');
    }

    public function testInvalidCodeKeepsTheSessionUnverified(): void
    {
        $user = $this->loginEnrolledAdmin();

        $this->postJson('/admin/two-factor/challenge', ['code' => $this->wrongCode($user)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('code');

        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    public function testSixthAttemptIsThrottledEvenWithAValidCode(): void
    {
        $user = $this->loginEnrolledAdmin();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/admin/two-factor/challenge', ['code' => $this->wrongCode($user)])->assertStatus(422);
        }

        $response = $this->postJson('/admin/two-factor/challenge', ['code' => $this->currentCode($user)]);

        $response->assertStatus(429)->assertJsonValidationErrors('code');
        self::assertMatchesRegularExpression('/\d+ seconds/', $response->json('errors.code.0'));
        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor/challenge'));
    }

    private function loginEnrolledAdmin(): Model&TwoFactorAuthenticatable
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->enrol($user);

        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        return $user->fresh();
    }
}
