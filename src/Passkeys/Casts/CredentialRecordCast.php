<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys\Casts;

use Brackets\AdminAuth\Passkeys\PasskeySerializer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Override;
use Webauthn\CredentialRecord;

/**
 * Stores the WebAuthn credential record (public key, counter, transports, user handle) as JSON.
 *
 * @implements CastsAttributes<CredentialRecord, CredentialRecord>
 */
final class CredentialRecordCast implements CastsAttributes
{
    /**
     * @param array<string, string|int|float|bool|null> $attributes
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CredentialRecord
    {
        if ($value === null) {
            return null;
        }

        return $this->serializer()->fromJson((string) $value, CredentialRecord::class);
    }

    /**
     * @param array<string, string|int|float|bool|null> $attributes
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    #[Override]
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof CredentialRecord) {
            throw new InvalidArgumentException(
                sprintf('The %s attribute must be a %s.', $key, CredentialRecord::class),
            );
        }

        return $this->serializer()->toJson($value);
    }

    private function serializer(): PasskeySerializer
    {
        // Eloquent instantiates casts itself, so the serializer can't be injected.
        return Container::getInstance()->make(PasskeySerializer::class);
    }
}
