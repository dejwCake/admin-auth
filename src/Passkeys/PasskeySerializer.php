<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorResponse;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;

/**
 * JSON (de)serialisation of the WebAuthn objects: options sent to the browser, responses from it and
 * the stored credential records. Only `none` attestation is supported.
 */
final readonly class PasskeySerializer
{
    private SerializerInterface $serializer;

    public function __construct()
    {
        $this->serializer = new WebauthnSerializerFactory(AttestationStatementSupportManager::create())->create();
    }

    public function toJson(object $value): string
    {
        return $this->serializer->serialize($value, 'json', [
            AbstractObjectNormalizer::SKIP_NULL_VALUES => true,
            JsonEncode::OPTIONS => JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        ]);
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    public function toArray(object $value): array
    {
        return json_decode($this->toJson($value), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * The authenticator response of the credential JSON the browser library posts.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     */
    public function authenticatorResponse(array $credential): AuthenticatorResponse
    {
        return $this->fromArray($credential, PublicKeyCredential::class)->response;
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function fromJson(string $json, string $class): object
    {
        return $this->serializer->deserialize($json, $class, 'json');
    }

    /**
     * @template T of object
     * @param array<string, array|string|int|float|bool|null> $data
     * @param class-string<T> $class
     * @return T
     */
    public function fromArray(array $data, string $class): object
    {
        return $this->fromJson(json_encode($data, JSON_THROW_ON_ERROR), $class);
    }
}
