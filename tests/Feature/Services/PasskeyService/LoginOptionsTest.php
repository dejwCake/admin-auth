<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Services\PasskeyService;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;

class LoginOptionsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testOptionsAreDiscoverableAndRequireUserVerification(): void
    {
        $options = $this->passkeyService()->loginOptions($this->passkeySession());

        self::assertEmpty($options['allowCredentials'] ?? []);
        self::assertSame('required', $options['userVerification']);
        self::assertSame('localhost', $options['rpId']);
    }
}
