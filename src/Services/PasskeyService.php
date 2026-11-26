<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Passkeys\PasskeyChallengeStore;
use Brackets\AdminAuth\Passkeys\PasskeyOptionsFactory;
use Brackets\AdminAuth\Passkeys\PasskeyRejection;
use Brackets\AdminAuth\Passkeys\PasskeyVerifier;
use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;
use Illuminate\Validation\ValidationException;
use Webauthn\PublicKeyCredentialRequestOptions;

/**
 * The passkey ceremonies of an admin: adding a passkey, the passwordless login and the two-factor challenge.
 * Every challenge is single-use and expires.
 */
final readonly class PasskeyService
{
    public const int TIMEOUT_SECONDS = PasskeyOptionsFactory::TIMEOUT_SECONDS;

    public function __construct(
        private PasskeyOptionsFactory $passkeyOptionsFactory,
        private PasskeyChallengeStore $passkeyChallengeStore,
        private PasskeyVerifier $passkeyVerifier,
        private PasskeyRejection $passkeyRejection,
        private TwoFactorSession $twoFactorSession,
        private SchemaCheck $schemaCheck,
    ) {
    }

    /**
     * Whether the passkeys table exists (the migration has run).
     */
    public function isAvailable(): bool
    {
        return $this->schemaCheck->hasTable(new AdminPasskey());
    }

    /**
     * Whether the admin model supports passkeys and the table exists.
     */
    public function isAvailableFor(Authenticatable $user): bool
    {
        return $user instanceof PasskeyAuthenticatable && $this->isAvailable();
    }

    public function hasPasskeys(Authenticatable $user): bool
    {
        return $user instanceof PasskeyAuthenticatable && $this->isAvailable() && $user->passkeys()->exists();
    }

    public function countFor(Authenticatable $user): int
    {
        return $user instanceof PasskeyAuthenticatable && $this->isAvailable() ? $user->passkeys()->count() : 0;
    }

    /**
     * Options to create a passkey, excluding the admin's existing ones.
     *
     * @return array<string, array|string|int|float|bool|null>
     */
    public function registrationOptions(Session $session, PasskeyAuthenticatable $user): array
    {
        $options = $this->passkeyOptionsFactory->creation($user->webAuthnUserEntity(), $user->passkeys()->get());

        return $this->passkeyChallengeStore->put($session, PasskeyChallengeStore::REGISTER, $options);
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    public function loginOptions(Session $session): array
    {
        return $this->passkeyChallengeStore->put(
            $session,
            PasskeyChallengeStore::LOGIN,
            $this->passkeyOptionsFactory->login(),
        );
    }

    /**
     * Options limited to the admin's own passkeys.
     *
     * @return array<string, array|string|int|float|bool|null>
     */
    public function challengeOptions(Session $session, PasskeyAuthenticatable $user): array
    {
        return $this->passkeyChallengeStore->put(
            $session,
            PasskeyChallengeStore::CHALLENGE,
            $this->passkeyOptionsFactory->challenge($user->passkeys()->get()),
        );
    }

    /**
     * Verify the browser's response and store the new passkey. Adding one counts as passing two-factor in
     * this session, as confirming TOTP does, so a forced admin who enrols with it continues.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the response is invalid, expired, reused or already stored
     */
    public function register(
        Session $session,
        PasskeyAuthenticatable $user,
        array $credential,
        string $name,
    ): AdminPasskey {
        $options = $this->passkeyChallengeStore->pullCreation($session);
        $credentialRecord = $this->passkeyVerifier->verifyRegistration($credential, $options);

        $credentialId = AdminPasskey::encodeCredentialId($credentialRecord->publicKeyCredentialId);
        $passkey = $user->passkeys()->make(['name' => $name]);
        $passkey->forceFill(['credential_id' => $credentialId, 'credential' => $credentialRecord]);
        $isStored = AdminPasskey::query()->where('credential_id_hash', $passkey->credential_id_hash)->exists();
        if ($isStored || !$passkey->saveUnlessDuplicate()) {
            throw $this->passkeyRejection->reject('The credential is already stored.', $credential);
        }
        $this->twoFactorSession->markVerified($session, $user);

        return $passkey;
    }

    /**
     * Verify a passwordless login response; returns the passkey, whose admin is not loaded or checked yet.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the response is invalid, expired, reused or of an unknown passkey
     */
    public function authenticate(Session $session, array $credential): AdminPasskey
    {
        $options = $this->passkeyChallengeStore->pullRequest($session, PasskeyChallengeStore::LOGIN);

        return $this->verify($this->find(null, $credential), $credential, $options);
    }

    /**
     * Verify a two-factor challenge response, which must be signed by one of the admin's own passkeys.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the response is invalid, expired, reused or not the admin's
     */
    public function verifyFor(Session $session, PasskeyAuthenticatable $user, array $credential): AdminPasskey
    {
        $options = $this->passkeyChallengeStore->pullRequest($session, PasskeyChallengeStore::CHALLENGE);

        return $this->verify($this->find($user, $credential), $credential, $options);
    }

    /**
     * Start every ceremony anew.
     */
    public function forget(Session $session): void
    {
        $this->passkeyChallengeStore->forget($session);
    }

    /* ************************ HELPERS ************************ */

    /**
     * The stored passkey the response names: any admin's, or only the given admin's.
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException for an unknown passkey
     */
    private function find(?PasskeyAuthenticatable $user, array $credential): AdminPasskey
    {
        $credentialId = is_string($credential['rawId'] ?? null) ? $credential['rawId'] : '';
        $query = $user?->passkeys() ?? AdminPasskey::query();
        $passkey = $credentialId !== ''
            ? $query->where('credential_id_hash', AdminPasskey::hashCredentialId($credentialId))->first()
            : null;
        if (!$passkey instanceof AdminPasskey) {
            throw $this->passkeyRejection->reject('Unknown passkey.', $credential);
        }

        return $passkey;
    }

    /**
     * Check the assertion against the stored record, then record the use (new counter, last used).
     *
     * @param array<string, array|string|int|float|bool|null> $credential
     * @throws ValidationException when the assertion is rejected
     */
    private function verify(
        AdminPasskey $passkey,
        array $credential,
        PublicKeyCredentialRequestOptions $options,
    ): AdminPasskey {
        // The user handle the passkey was created with must be its admin's primary key.
        $credentialRecord = $this->passkeyVerifier->verifyAssertion(
            $credential,
            $passkey->credential,
            $options,
            (string) $passkey->admin_user_id,
        );

        $passkey->forceFill(['credential' => $credentialRecord, 'last_used_at' => CarbonImmutable::now()])->save();

        return $passkey;
    }
}
