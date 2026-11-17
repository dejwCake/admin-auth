<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Models\AdminPasskey;
use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;
use Carbon\CarbonImmutable;

class AuthenticateTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testValidResponseReturnsThePasskeyAndRecordsTheUse(): void
    {
        CarbonImmutable::setTestNow('2026-11-16 10:00:00');
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $stored = $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $passkey = $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user),
        );

        self::assertSame($stored->id, $passkey->id);
        $fresh = $stored->fresh();
        self::assertInstanceOf(AdminPasskey::class, $fresh);
        self::assertSame('2026-11-16 10:00:00', $fresh->last_used_at?->format('Y-m-d H:i:s'));
        self::assertSame(1, $fresh->credential->counter);
    }

    public function testForeignOriginIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user, origin: 'https://evil.example.org'),
        ));
    }

    public function testResponseWithoutUserVerificationIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user, false),
        ));
    }

    public function testReusedChallengeIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());
        $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user),
        );

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user),
        ));
    }

    public function testExpiredChallengeIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());
        $this->travel(PasskeyService::TIMEOUT_SECONDS + 1)->seconds();

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user),
        ));
    }

    public function testCounterRegressionIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator(counter: 10);
        $stored = $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $user, counter: 5),
        ));

        self::assertNull($stored->fresh()?->last_used_at);
    }

    public function testZeroCounterOfASyncedPasskeyIsAccepted(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);

        foreach ([1, 2] as $attempt) {
            $options = $this->passkeyService()->loginOptions($this->passkeySession());
            $passkey = $this->passkeyService()->authenticate(
                $this->passkeySession(),
                $this->assertion($authenticator, $options, $user, counter: 0),
            );
            self::assertSame(0, $passkey->credential->counter, sprintf('attempt %d', $attempt));
        }
    }

    public function testUnknownPasskeyIsRejected(): void
    {
        $user = $this->createAdmin();
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion(new VirtualAuthenticator(), $options, $user),
        ));
    }

    public function testUserHandleOfAnotherAdminIsRejected(): void
    {
        $john = $this->createAdmin();
        $jane = $this->createAdmin('jane@example.com');
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($john, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->authenticate(
            $this->passkeySession(),
            $this->assertion($authenticator, $options, $jane),
        ));
    }
}
