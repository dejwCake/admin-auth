<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Services\PasskeyService;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;

class RegisterTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testValidResponseStoresThePasskey(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);

        $passkey = $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            $authenticator->register($options, $this->origin()),
            'MacBook',
        );

        self::assertSame('MacBook', $passkey->name);
        self::assertSame($authenticator->credentialIdBase64Url(), $passkey->credential_id);
        self::assertSame(
            hash('sha256', $authenticator->credentialIdBase64Url()),
            $passkey->credential_id_hash,
        );
        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
    }

    public function testForeignOriginIsRejected(): void
    {
        $user = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);

        $this->expectPasskeyError(fn () => $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            (new VirtualAuthenticator())->register($options, 'https://evil.example.org'),
            'MacBook',
        ));

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testResponseWithoutUserVerificationIsRejected(): void
    {
        $user = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);

        $this->expectPasskeyError(fn () => $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            (new VirtualAuthenticator())->register($options, $this->origin(), false),
            'MacBook',
        ));

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testReusedChallengeIsRejected(): void
    {
        $user = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);
        $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            (new VirtualAuthenticator())->register($options, $this->origin()),
            'MacBook',
        );

        $this->expectPasskeyError(fn () => $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            (new VirtualAuthenticator())->register($options, $this->origin()),
            'YubiKey',
        ));

        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
    }

    public function testExpiredChallengeIsRejected(): void
    {
        $user = $this->createAdmin();
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);
        $this->travel(PasskeyService::TIMEOUT_SECONDS + 1)->seconds();

        $this->expectPasskeyError(fn () => $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            (new VirtualAuthenticator())->register($options, $this->origin()),
            'MacBook',
        ));

        self::assertSame(0, $user->passkeys()->count());
    }

    public function testSameAuthenticatorTwiceIsRejected(): void
    {
        $user = $this->createAdmin();
        $authenticator = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $authenticator);
        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);

        $this->expectPasskeyError(fn () => $this->passkeyService()->register(
            $this->passkeySession(),
            $user,
            $authenticator->register($options, $this->origin()),
            'Again',
        ));

        self::assertSame(['MacBook'], $user->passkeys()->pluck('name')->all());
    }
}
