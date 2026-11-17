<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Passkeys\PasskeyConfig;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Stores passkeys of a virtual authenticator for an admin, and answers the options a test got from the app.
 */
trait PasskeyHelpers
{
    protected function origin(): string
    {
        return $this->app->make(PasskeyConfig::class)->allowedOrigins()[0];
    }

    /**
     * Store a passkey of the authenticator for the admin, as a finished registration would.
     */
    protected function addPasskey(
        Model&PasskeyAuthenticatable $user,
        string $name = 'MacBook',
        ?VirtualAuthenticator $authenticator = null,
    ): AdminPasskey {
        $authenticator ??= new VirtualAuthenticator();

        $passkey = $user->passkeys()->make(['name' => $name]);
        $passkey->forceFill([
            'credential_id' => $authenticator->credentialIdBase64Url(),
            'credential' => CredentialRecord::create(
                $authenticator->credentialId(),
                'public-key',
                ['internal'],
                'none',
                EmptyTrustPath::create(),
                Uuid::fromString('00000000-0000-0000-0000-000000000000'),
                $authenticator->coseKey(),
                (string) $user->getAuthIdentifier(),
                $authenticator->counter(),
            ),
        ])->save();

        return $passkey;
    }

    /**
     * Sign request options (decoded JSON from the app) as the admin's authenticator.
     *
     * @param array<string, array|string|int|float|bool|null> $options
     * @return array<string, array|string|int|float|bool|null>
     */
    protected function assertion(
        VirtualAuthenticator $authenticator,
        array $options,
        Model&PasskeyAuthenticatable $user,
        bool $userVerified = true,
        ?string $origin = null,
        ?int $counter = null,
    ): array {
        return $authenticator->assert(
            $options,
            $origin ?? $this->origin(),
            (string) $user->getAuthIdentifier(),
            $userVerified,
            $counter,
        );
    }

    /**
     * Logged in and past the two-factor challenge.
     */
    protected function actingAsVerified(Authenticatable $user): static
    {
        return $this->actingAs($user, $this->adminAuthGuard)
            ->withSession([TwoFactorSession::VERIFIED => $user->getAuthIdentifier()]);
    }

    protected function passkeyService(): PasskeyService
    {
        return $this->app->make(PasskeyService::class);
    }

    protected function passkeySession(): Session
    {
        return $this->app->make(Session::class);
    }

    /**
     * The action must fail with the generic validation error on `passkey`.
     */
    protected function expectPasskeyError(callable $action): void
    {
        try {
            $action();
            self::fail('The passkey response was accepted.');
        } catch (ValidationException $validationException) {
            self::assertArrayHasKey('passkey', $validationException->errors());
        }
    }
}
