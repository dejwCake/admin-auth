<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * Keeps the options of a ceremony in the session until its response: one response per options, and none
 * after they expired.
 */
final readonly class PasskeyChallengeStore
{
    public const string REGISTER = 'register';
    public const string LOGIN = 'login';
    public const string CHALLENGE = 'challenge';

    private const string SESSION_KEY = 'admin-auth.passkeys.%s';

    public function __construct(
        private PasskeySerializer $passkeySerializer,
        private PasskeyRejection $passkeyRejection,
    ) {
    }

    /**
     * Keep the options, replacing earlier ones of the ceremony; returns them as sent to the browser.
     *
     * @return array<string, array|string|int|float|bool|null>
     */
    public function put(
        Session $session,
        string $ceremony,
        PublicKeyCredentialCreationOptions|PublicKeyCredentialRequestOptions $options,
    ): array {
        $session->put(sprintf(self::SESSION_KEY, $ceremony), [
            'options' => $this->passkeySerializer->toJson($options),
            'issued_at' => CarbonImmutable::now()->getTimestamp(),
        ]);

        return $this->passkeySerializer->toArray($options);
    }

    /**
     * @throws ValidationException when none were issued, they were used or they expired
     */
    public function pullCreation(Session $session): PublicKeyCredentialCreationOptions
    {
        return $this->passkeySerializer->fromJson(
            $this->pull($session, self::REGISTER),
            PublicKeyCredentialCreationOptions::class,
        );
    }

    /**
     * @throws ValidationException when none were issued, they were used or they expired
     */
    public function pullRequest(Session $session, string $ceremony): PublicKeyCredentialRequestOptions
    {
        return $this->passkeySerializer->fromJson(
            $this->pull($session, $ceremony),
            PublicKeyCredentialRequestOptions::class,
        );
    }

    /**
     * Start every ceremony anew.
     */
    public function forget(Session $session): void
    {
        $session->forget(array_map(
            static fn (string $ceremony): string => sprintf(self::SESSION_KEY, $ceremony),
            [self::REGISTER, self::LOGIN, self::CHALLENGE],
        ));
    }

    private function pull(Session $session, string $ceremony): string
    {
        $stored = $session->pull(sprintf(self::SESSION_KEY, $ceremony));
        if (!is_array($stored) || !is_string($stored['options'] ?? null)) {
            throw $this->passkeyRejection->reject(sprintf('No %s options were issued, or they were used.', $ceremony));
        }

        $age = CarbonImmutable::now()->getTimestamp() - (int) ($stored['issued_at'] ?? 0);
        if ($age > PasskeyOptionsFactory::TIMEOUT_SECONDS) {
            throw $this->passkeyRejection->reject(sprintf('The %s options expired.', $ceremony));
        }

        return $stored['options'];
    }
}
