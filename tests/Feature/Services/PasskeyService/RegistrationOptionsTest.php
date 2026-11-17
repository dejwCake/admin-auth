<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;

class RegistrationOptionsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testOptionsRequireADiscoverableVerifiedCredentialAndExcludeExistingOnes(): void
    {
        $user = $this->createAdmin();
        $existing = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $existing);

        $options = $this->passkeyService()->registrationOptions($this->passkeySession(), $user);

        self::assertSame('required', $options['authenticatorSelection']['residentKey']);
        self::assertSame('required', $options['authenticatorSelection']['userVerification']);
        self::assertSame('none', $options['attestation']);
        self::assertSame([$existing->credentialIdBase64Url()], array_column($options['excludeCredentials'], 'id'));
        self::assertSame(
            (string) $user->getAuthIdentifier(),
            VirtualAuthenticator::decodeBase64Url($options['user']['id']),
        );
    }
}
