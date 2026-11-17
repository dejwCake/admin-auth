<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Session\Session;

/**
 * Which admin passed two-factor in a session.
 */
final readonly class TwoFactorSession
{
    /**
     * Session key holding the id of the admin who passed two-factor in this session.
     */
    public const string VERIFIED = 'admin-auth.two_factor.verified';

    /**
     * Session key holding the id of the verified admin who impersonated the current one.
     */
    public const string IMPERSONATED_BY = 'admin-auth.two_factor.impersonated_by';

    /**
     * Session key holding the id of an admin found with no second factor and not forced to enrol, so the gate
     * skips its queries. Not a verification: handOver() ignores it.
     */
    public const string UNENROLLED = 'admin-auth.two_factor.unenrolled';

    public function markVerified(Session $session, Authenticatable $user): void
    {
        $session->put(self::VERIFIED, $user->getAuthIdentifier());
    }

    /**
     * Verified only for the admin who passed the challenge, never for another admin in the same session.
     */
    public function isVerified(Session $session, Authenticatable $user): bool
    {
        $verified = $session->get(self::VERIFIED);

        return $verified !== null && (string) $verified === (string) $user->getAuthIdentifier();
    }

    public function markUnenrolled(Session $session, Authenticatable $user): void
    {
        $session->put(self::UNENROLLED, $user->getAuthIdentifier());
    }

    public function isUnenrolled(Session $session, Authenticatable $user): bool
    {
        $unenrolled = $session->get(self::UNENROLLED);

        return $unenrolled !== null && (string) $unenrolled === (string) $user->getAuthIdentifier();
    }

    /**
     * Hand a verified session over to another admin logged in without a password (impersonal login).
     */
    public function handOver(Session $session, Authenticatable $user): void
    {
        $verified = $session->get(self::VERIFIED);
        // The key is only ever set after a real verification in this session.
        if ($verified === null || (string) $verified === (string) $user->getAuthIdentifier()) {
            return;
        }

        $session->put(self::IMPERSONATED_BY, $verified);
        $this->markVerified($session, $user);
    }

    public function forget(Session $session): void
    {
        $session->forget([self::VERIFIED, self::IMPERSONATED_BY, self::UNENROLLED]);
    }
}
