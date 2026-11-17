<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Brackets\AdminAuth\Models\AdminPasskey;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * The options of each ceremony, with a fresh random challenge.
 */
final readonly class PasskeyOptionsFactory
{
    public const int TIMEOUT_SECONDS = 300;

    /**
     * ES256 and RS256, the algorithms the library verifies by default.
     */
    private const array ALGORITHMS = [-7, -257];

    public function __construct(private PasskeyConfig $passkeyConfig)
    {
    }

    /**
     * Create a passkey: discoverable, with user verification, none of the given ones again.
     *
     * @param iterable<AdminPasskey> $existing
     */
    public function creation(
        PublicKeyCredentialUserEntity $user,
        iterable $existing = [],
    ): PublicKeyCredentialCreationOptions {
        return PublicKeyCredentialCreationOptions::create(
            PublicKeyCredentialRpEntity::create($this->passkeyConfig->rpName(), $this->passkeyConfig->rpId()),
            $user,
            random_bytes(32),
            array_map(PublicKeyCredentialParameters::createPk(...), self::ALGORITHMS),
            AuthenticatorSelectionCriteria::create(
                userVerification: AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE,
            $this->descriptors($existing),
            self::TIMEOUT_SECONDS * 1000,
        );
    }

    /**
     * Sign in with any discoverable passkey, with user verification.
     */
    public function login(): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->passkeyConfig->rpId(),
            userVerification: PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
            timeout: self::TIMEOUT_SECONDS * 1000,
        );
    }

    /**
     * The two-factor challenge: only the given passkeys. The password was the first factor, so possession is
     * enough and user verification is only preferred.
     *
     * @param iterable<AdminPasskey> $allowed
     */
    public function challenge(iterable $allowed): PublicKeyCredentialRequestOptions
    {
        return PublicKeyCredentialRequestOptions::create(
            random_bytes(32),
            $this->passkeyConfig->rpId(),
            $this->descriptors($allowed),
            PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_PREFERRED,
            self::TIMEOUT_SECONDS * 1000,
        );
    }

    /**
     * @param iterable<AdminPasskey> $passkeys
     * @return array<int, PublicKeyCredentialDescriptor>
     */
    private function descriptors(iterable $passkeys): array
    {
        $descriptors = [];
        foreach ($passkeys as $passkey) {
            $descriptors[] = $passkey->credential->getPublicKeyCredentialDescriptor();
        }

        return $descriptors;
    }
}
