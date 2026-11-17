<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\TwoFactorChallengeController;

use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;

class PasskeyOptionsUserTest extends UserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testOptionsAllowOnlyTheAdminsOwnPasskeys(): void
    {
        $authenticator = new VirtualAuthenticator();
        $this->loginAdminWithPasskey($authenticator);
        $this->addPasskey($this->createAdmin('jane@example.com'), 'Jane phone');

        $response = $this->postJson('/admin/two-factor/challenge/passkey/options');

        $response->assertOk();
        self::assertSame(
            [$authenticator->credentialIdBase64Url()],
            array_column($response->json('allowCredentials'), 'id'),
        );
        self::assertSame('preferred', $response->json('userVerification'));
    }

    public function testOptionsWithoutTheTableAreRejected(): void
    {
        $this->loginAdminWithPasskey(new VirtualAuthenticator());
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->postJson('/admin/two-factor/challenge/passkey/options')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('passkey');
    }

    /**
     * An admin with the passkey, logged in with their password and not verified yet.
     */
    private function loginAdminWithPasskey(
        VirtualAuthenticator $authenticator,
    ): Model&TwoFactorAuthenticatable&PasskeyAuthenticatable {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $this->post('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD]);

        return $user;
    }
}
