<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;

class VerifyForTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testOwnPasskeyIsAcceptedWithoutUserVerification(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $stored = $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->challengeOptions($this->passkeySession(), $user);

        $passkey = $this->passkeyService()->verifyFor(
            $this->passkeySession(),
            $user,
            $this->assertion($authenticator, $options, $user, false),
        );

        self::assertSame($stored->id, $passkey->id);
    }

    public function testAnotherAdminsPasskeyIsRejected(): void
    {
        $user = $this->createAdmin();
        $this->addPasskey($user, 'MacBook');
        $jane = $this->createAdmin('jane@example.com');
        $janes = new VirtualAuthenticator();
        $this->addPasskey($jane, 'Phone', $janes);
        $options = $this->passkeyService()->challengeOptions($this->passkeySession(), $user);

        $this->expectPasskeyError(fn () => $this->passkeyService()->verifyFor(
            $this->passkeySession(),
            $user,
            $this->assertion($janes, $options, $jane),
        ));
    }

    public function testLoginOptionsDoNotAnswerTheChallenge(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        $this->expectPasskeyError(fn () => $this->passkeyService()->verifyFor(
            $this->passkeySession(),
            $user,
            $this->assertion($authenticator, $options, $user),
        ));
    }
}
