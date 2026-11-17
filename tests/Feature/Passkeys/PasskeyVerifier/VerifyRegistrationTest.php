<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Passkeys\PasskeyVerifier;

use Brackets\AdminAuth\Passkeys\PasskeyOptionsFactory;
use Brackets\AdminAuth\Passkeys\PasskeySerializer;
use Brackets\AdminAuth\Passkeys\PasskeyVerifier;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Also proves that the library accepts the virtual authenticator, which every passkey test relies on.
 */
class VerifyRegistrationTest extends AdminUserTestCase
{
    use PasskeyHelpers;

    private const string USER_HANDLE = '42';

    public function testValidResponseGivesTheCredentialRecord(): void
    {
        $authenticator = new VirtualAuthenticator();
        $options = $this->creationOptions();

        $credentialRecord = $this->verifier()->verifyRegistration(
            $authenticator->register($this->toArray($options), $this->origin()),
            $options,
        );

        self::assertSame($authenticator->credentialId(), $credentialRecord->publicKeyCredentialId);
        self::assertSame($authenticator->coseKey(), $credentialRecord->credentialPublicKey);
        self::assertSame(self::USER_HANDLE, $credentialRecord->userHandle);
    }

    public function testResponseWithoutUserVerificationIsRejected(): void
    {
        $options = $this->creationOptions();

        $this->expectException(ValidationException::class);

        $this->verifier()->verifyRegistration(
            (new VirtualAuthenticator())->register($this->toArray($options), $this->origin(), false),
            $options,
        );
    }

    public function testForeignOriginIsRejected(): void
    {
        $options = $this->creationOptions();

        $this->expectException(ValidationException::class);

        $this->verifier()->verifyRegistration(
            (new VirtualAuthenticator())->register($this->toArray($options), 'https://evil.example.org'),
            $options,
        );
    }

    public function testResponseForOtherOptionsIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->verifier()->verifyRegistration(
            (new VirtualAuthenticator())->register($this->toArray($this->creationOptions()), $this->origin()),
            $this->creationOptions(),
        );
    }

    public function testAssertionInsteadOfAnAttestationIsRejected(): void
    {
        $options = $this->creationOptions();
        $login = $this->toArray($this->app->make(PasskeyOptionsFactory::class)->login());

        $this->expectException(ValidationException::class);

        $this->verifier()->verifyRegistration(
            (new VirtualAuthenticator())->assert($login, $this->origin(), self::USER_HANDLE),
            $options,
        );
    }

    public function testMalformedResponseIsRejected(): void
    {
        $this->expectException(ValidationException::class);

        $this->verifier()->verifyRegistration(['id' => 'abc', 'response' => 'nonsense'], $this->creationOptions());
    }

    private function creationOptions(): PublicKeyCredentialCreationOptions
    {
        return $this->app->make(PasskeyOptionsFactory::class)->creation(
            PublicKeyCredentialUserEntity::create('john@example.com', self::USER_HANDLE, 'John'),
        );
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    private function toArray(object $options): array
    {
        return $this->app->make(PasskeySerializer::class)->toArray($options);
    }

    private function verifier(): PasskeyVerifier
    {
        return $this->app->make(PasskeyVerifier::class);
    }
}
