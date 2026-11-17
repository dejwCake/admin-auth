<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Illuminate\Validation\ValidationException;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * The generic validation error on `passkey` for any rejected ceremony; the reason goes to the log only.
 */
final readonly class PasskeyRejection
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * @param array<string, array|string|int|float|bool|null> $credential
     */
    public function reject(string $reason, array $credential = [], ?Throwable $throwable = null): ValidationException
    {
        $this->logger->warning('A passkey response was rejected.', [
            'reason' => $reason,
            'origin' => $this->originOf($credential),
            'exception' => $throwable,
        ]);

        return ValidationException::withMessages([
            'passkey' => [trans('brackets/admin-auth::admin.passkeys.invalid')],
        ]);
    }

    /**
     * The origin the browser signed for, to tell a wrong ADMIN_PASSKEYS_ALLOWED_ORIGINS from an attack.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     */
    private function originOf(array $credential): ?string
    {
        $response = $credential['response'] ?? null;
        $clientDataJson = is_array($response) ? ($response['clientDataJSON'] ?? null) : null;
        if (!is_string($clientDataJson)) {
            return null;
        }

        try {
            $clientData = json_decode(Base64UrlSafe::decode($clientDataJson), true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return null;
        }

        return is_array($clientData) && is_string($clientData['origin'] ?? null) ? $clientData['origin'] : null;
    }
}
