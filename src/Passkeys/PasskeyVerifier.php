<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Illuminate\Validation\ValidationException;
use Throwable;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Checks the browser's responses with the WebAuthn library: origin, challenge, relying party, user presence
 * and verification as the options ask, signature and counter.
 */
final readonly class PasskeyVerifier
{
    public function __construct(
        private PasskeyConfig $passkeyConfig,
        private PasskeySerializer $passkeySerializer,
        private PasskeyRejection $passkeyRejection,
    ) {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the response is not valid for the options
     */
    public function verifyRegistration(array $credential, PublicKeyCredentialCreationOptions $options): CredentialRecord
    {
        $response = $this->authenticatorResponse($credential);
        if (!$response instanceof AuthenticatorAttestationResponse) {
            throw $this->passkeyRejection->reject('Not an attestation response.', $credential);
        }

        try {
            return AuthenticatorAttestationResponseValidator::create(
                $this->passkeyConfig->ceremonyStepManagerFactory()->creationCeremony(),
            )
                ->check($response, $options, $this->passkeyConfig->rpId());
        } catch (Throwable $throwable) {
            throw $this->passkeyRejection->reject($throwable->getMessage(), $credential, $throwable);
        }
    }

    /**
     * Returns the record with the new counter.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the response is not valid for the options, the record or the user
     */
    public function verifyAssertion(
        array $credential,
        CredentialRecord $credentialRecord,
        PublicKeyCredentialRequestOptions $options,
        string $userHandle,
    ): CredentialRecord {
        $response = $this->authenticatorResponse($credential);
        if (!$response instanceof AuthenticatorAssertionResponse) {
            throw $this->passkeyRejection->reject('Not an assertion response.', $credential);
        }

        try {
            return AuthenticatorAssertionResponseValidator::create(
                $this->passkeyConfig->ceremonyStepManagerFactory()->requestCeremony(),
            )
                ->check($credentialRecord, $response, $options, $this->passkeyConfig->rpId(), $userHandle);
        } catch (Throwable $throwable) {
            throw $this->passkeyRejection->reject($throwable->getMessage(), $credential, $throwable);
        }
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the JSON is not a public key credential
     */
    private function authenticatorResponse(array $credential): object
    {
        try {
            return $this->passkeySerializer->authenticatorResponse($credential);
        } catch (Throwable $throwable) {
            throw $this->passkeyRejection->reject($throwable->getMessage(), $credential, $throwable);
        }
    }
}
