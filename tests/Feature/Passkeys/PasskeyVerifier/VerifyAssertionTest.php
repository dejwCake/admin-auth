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
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Also proves that the library accepts the virtual authenticator, which every passkey test relies on.
 */
class VerifyAssertionTest extends AdminUserTestCase
{
    use PasskeyHelpers;

    private const string USER_HANDLE = '42';

    public function testValidAssertionGivesTheNewCounter(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);

        $checked = $this->verify($authenticator, $credentialRecord, $this->factory()->login());

        self::assertSame(1, $checked->counter);
    }

    public function testMissingUserVerificationIsRejectedWhenRequired(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);

        $this->expectException(ValidationException::class);

        $this->verify($authenticator, $credentialRecord, $this->factory()->login(), userVerified: false);
    }

    public function testMissingUserVerificationIsAcceptedWhenOnlyPreferred(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);

        $checked = $this->verify(
            $authenticator,
            $credentialRecord,
            $this->factory()->challenge([]),
            userVerified: false,
        );

        self::assertSame(1, $checked->counter);
    }

    public function testForeignOriginIsRejected(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);

        $this->expectException(ValidationException::class);

        $this->verify($authenticator, $credentialRecord, $this->factory()->login(), origin: 'https://evil.example.org');
    }

    public function testAnotherUserHandleIsRejected(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);
        $options = $this->factory()->login();

        $this->expectException(ValidationException::class);

        $this->verifier()->verifyAssertion(
            $authenticator->assert($this->toArray($options), $this->origin(), self::USER_HANDLE),
            $credentialRecord,
            $options,
            '43',
        );
    }

    public function testSignatureOfAnotherKeyIsRejected(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);
        // Same credential id, different key: the stored public key does not match the signature.
        $impostor = new VirtualAuthenticator($authenticator->credentialId());

        $this->expectException(ValidationException::class);

        $this->verify($impostor, $credentialRecord, $this->factory()->login());
    }

    public function testCounterRegressionIsRejected(): void
    {
        $authenticator = new VirtualAuthenticator(counter: 10);
        $credentialRecord = $this->register($authenticator);

        $this->expectException(ValidationException::class);

        $this->verify($authenticator, $credentialRecord, $this->factory()->login(), counter: 5);
    }

    public function testZeroCounterOfASyncedPasskeyIsAccepted(): void
    {
        $authenticator = new VirtualAuthenticator();
        $credentialRecord = $this->register($authenticator);

        $checked = $this->verify($authenticator, $credentialRecord, $this->factory()->login(), counter: 0);

        self::assertSame(0, $checked->counter);
    }

    private function register(VirtualAuthenticator $authenticator): CredentialRecord
    {
        $options = $this->factory()->creation(
            PublicKeyCredentialUserEntity::create('john@example.com', self::USER_HANDLE, 'John'),
        );

        return $this->verifier()->verifyRegistration(
            $authenticator->register($this->toArray($options), $this->origin()),
            $options,
        );
    }

    private function verify(
        VirtualAuthenticator $authenticator,
        CredentialRecord $credentialRecord,
        PublicKeyCredentialRequestOptions $options,
        bool $userVerified = true,
        ?string $origin = null,
        ?int $counter = null,
    ): CredentialRecord {
        return $this->verifier()->verifyAssertion(
            $authenticator->assert(
                $this->toArray($options),
                $origin ?? $this->origin(),
                self::USER_HANDLE,
                $userVerified,
                $counter,
            ),
            $credentialRecord,
            $options,
            self::USER_HANDLE,
        );
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    private function toArray(object $options): array
    {
        return $this->app->make(PasskeySerializer::class)->toArray($options);
    }

    private function factory(): PasskeyOptionsFactory
    {
        return $this->app->make(PasskeyOptionsFactory::class);
    }

    private function verifier(): PasskeyVerifier
    {
        return $this->app->make(PasskeyVerifier::class);
    }
}
