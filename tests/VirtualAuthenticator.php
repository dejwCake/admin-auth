<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use CBOR\ByteStringObject;
use CBOR\MapObject;
use CBOR\NegativeIntegerObject;
use CBOR\TextStringObject;
use CBOR\UnsignedIntegerObject;
use OpenSSLAsymmetricKey;
use RuntimeException;

/**
 * A software passkey for tests: an EC P-256 key that answers WebAuthn options with real, signed responses
 * (`none` attestation), in the JSON shape the browser library posts.
 */
final class VirtualAuthenticator
{
    private const int FLAG_USER_PRESENT = 0x01;
    private const int FLAG_USER_VERIFIED = 0x04;
    private const int FLAG_ATTESTED_CREDENTIAL_DATA = 0x40;

    private readonly string $credentialId;

    private OpenSSLAsymmetricKey $key;

    public function __construct(?string $credentialId = null, private int $counter = 0)
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        if ($key === false) {
            throw new RuntimeException('Cannot create an EC key.');
        }

        $this->key = $key;
        $this->credentialId = $credentialId ?? random_bytes(32);
    }

    public static function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function decodeBase64Url(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }

    public function credentialId(): string
    {
        return $this->credentialId;
    }

    public function counter(): int
    {
        return $this->counter;
    }

    public function credentialIdBase64Url(): string
    {
        return self::base64Url($this->credentialId);
    }

    /**
     * Answer creation options (as sent to the browser) with an attestation response.
     *
     * @param array<string, array|string|int|float|bool|null> $options
     * @return array<string, array|string|int|float|bool|null>
     */
    public function register(array $options, string $origin, bool $userVerified = true): array
    {
        $flags = self::FLAG_USER_PRESENT | self::FLAG_ATTESTED_CREDENTIAL_DATA
            | ($userVerified ? self::FLAG_USER_VERIFIED : 0);
        $authData = sprintf(
            '%s%s%s%s',
            $this->authDataHeader((string) $options['rp']['id'], $flags),
            str_repeat("\0", 16),
            pack('n', strlen($this->credentialId)),
            sprintf('%s%s', $this->credentialId, $this->coseKey()),
        );

        $attestationObject = MapObject::create()
            ->add(TextStringObject::create('fmt'), TextStringObject::create('none'))
            ->add(TextStringObject::create('attStmt'), MapObject::create())
            ->add(TextStringObject::create('authData'), ByteStringObject::create($authData));

        return [
            'id' => $this->credentialIdBase64Url(),
            'rawId' => $this->credentialIdBase64Url(),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64Url($this->clientData('webauthn.create', $options, $origin)),
                'attestationObject' => self::base64Url((string) $attestationObject),
                'transports' => ['internal'],
            ],
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
        ];
    }

    /**
     * Answer request options with a signed assertion; the counter goes up by one unless given.
     *
     * @param array<string, array|string|int|float|bool|null> $options
     * @return array<string, array|string|int|float|bool|null>
     */
    public function assert(
        array $options,
        string $origin,
        string $userHandle,
        bool $userVerified = true,
        ?int $counter = null,
    ): array {
        $this->counter = $counter ?? $this->counter + 1;
        $flags = self::FLAG_USER_PRESENT | ($userVerified ? self::FLAG_USER_VERIFIED : 0);
        $authData = $this->authDataHeader((string) $options['rpId'], $flags);
        $clientData = $this->clientData('webauthn.get', $options, $origin);

        $signature = '';
        if (
            !openssl_sign(
                sprintf('%s%s', $authData, hash('sha256', $clientData, true)),
                $signature,
                $this->key,
                OPENSSL_ALGO_SHA256,
            )
        ) {
            throw new RuntimeException('Cannot sign the assertion.');
        }

        return [
            'id' => $this->credentialIdBase64Url(),
            'rawId' => $this->credentialIdBase64Url(),
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => self::base64Url($clientData),
                'authenticatorData' => self::base64Url($authData),
                'signature' => self::base64Url($signature),
                'userHandle' => self::base64Url($userHandle),
            ],
            'clientExtensionResults' => [],
            'authenticatorAttachment' => 'platform',
        ];
    }

    private function authDataHeader(string $rpId, int $flags): string
    {
        return sprintf('%s%s%s', hash('sha256', $rpId, true), chr($flags), pack('N', $this->counter));
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $options
     */
    private function clientData(string $type, array $options, string $origin): string
    {
        return json_encode([
            'type' => $type,
            'challenge' => $options['challenge'],
            'origin' => $origin,
            'crossOrigin' => false,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /**
     * The public key as a COSE EC2 key (ES256 on P-256).
     */
    public function coseKey(): string
    {
        $details = openssl_pkey_get_details($this->key);
        if ($details === false) {
            throw new RuntimeException('Cannot read the EC key.');
        }

        return (string) MapObject::create()
            ->add(UnsignedIntegerObject::create(1), UnsignedIntegerObject::create(2))
            ->add(UnsignedIntegerObject::create(3), NegativeIntegerObject::create(-7))
            ->add(NegativeIntegerObject::create(-1), UnsignedIntegerObject::create(1))
            ->add(
                NegativeIntegerObject::create(-2),
                ByteStringObject::create(str_pad($details['ec']['x'], 32, "\0", STR_PAD_LEFT)),
            )
            ->add(
                NegativeIntegerObject::create(-3),
                ByteStringObject::create(str_pad($details['ec']['y'], 32, "\0", STR_PAD_LEFT)),
            );
    }
}
