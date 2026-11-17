<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Passkeys\Traits;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Illuminate\Database\DatabaseManager;
use Webauthn\CredentialRecord;

class HasPasskeysTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testPasskeysAreOnlyTheAdminsOwn(): void
    {
        $john = $this->createAdmin();
        $jane = $this->createAdmin('jane@example.com');
        $this->addPasskey($john, 'MacBook');
        $this->addPasskey($john, 'YubiKey');
        $this->addPasskey($jane, 'Phone');

        self::assertSame(['MacBook', 'YubiKey'], $john->passkeys()->orderBy('id')->pluck('name')->all());
        self::assertSame(['Phone'], $jane->passkeys()->pluck('name')->all());
    }

    public function testSerialisedPasskeyHoldsNoCredentialData(): void
    {
        $passkey = $this->addPasskey($this->createAdmin());

        $json = $passkey->fresh()?->toJson() ?? '';

        self::assertStringNotContainsString('credential', $json);
        self::assertStringNotContainsString($passkey->credential_id, $json);
        self::assertStringContainsString('MacBook', $json);
    }

    public function testCredentialIsReadBackAsACredentialRecord(): void
    {
        $authenticator = new VirtualAuthenticator();
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook', $authenticator);

        $passkey = AdminPasskey::query()->firstOrFail();

        self::assertInstanceOf(CredentialRecord::class, $passkey->credential);
        self::assertSame($authenticator->credentialId(), $passkey->credential->publicKeyCredentialId);
        self::assertSame($authenticator->coseKey(), $passkey->credential->credentialPublicKey);
        self::assertSame((string) $user->getAuthIdentifier(), $passkey->credential->userHandle);
    }

    public function testCredentialIsStoredAsJson(): void
    {
        $this->addPasskey($this->createAdmin());

        $stored = $this->app->make(DatabaseManager::class)->table('admin_passkeys')->value('credential');

        self::assertIsArray(json_decode((string) $stored, true));
    }

    public function testOnlyTheNameIsFillable(): void
    {
        $passkey = new AdminPasskey(['name' => 'MacBook', 'credential_id' => 'abc', 'admin_user_id' => 1]);

        self::assertSame(['name' => 'MacBook'], $passkey->getAttributes());
    }

    public function testWebAuthnUserIsThePrimaryKeyAndTheEmail(): void
    {
        $user = $this->createAdmin();

        $userEntity = $user->webAuthnUserEntity();

        self::assertSame((string) $user->getAuthIdentifier(), $userEntity->id);
        self::assertSame('john@example.com', $userEntity->name);
        self::assertNotSame('', $userEntity->displayName);
    }
}
