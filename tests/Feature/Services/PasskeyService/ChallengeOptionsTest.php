<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\VirtualAuthenticator;

class ChallengeOptionsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testOptionsAllowOnlyTheAdminsPasskeysAndPreferUserVerification(): void
    {
        $user = $this->createAdmin();
        $own = new VirtualAuthenticator();
        $this->addPasskey($user, 'MacBook', $own);
        $this->addPasskey($this->createAdmin('jane@example.com'), 'Phone');

        $options = $this->passkeyService()->challengeOptions($this->passkeySession(), $user);

        self::assertSame([$own->credentialIdBase64Url()], array_column($options['allowCredentials'], 'id'));
        self::assertSame('preferred', $options['userVerification']);
    }
}
