<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Passkeys\PasskeyConfig;

use Brackets\AdminAuth\Passkeys\PasskeyConfig;
use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

class RpNameTest extends TestCase
{
    public function testDefaultIsTheAppName(): void
    {
        self::assertSame('Craftable', (new PasskeyConfig($this->config('https://example.com')))->rpName());
    }

    public function testCustomRelyingParty(): void
    {
        $passkeyConfig = new PasskeyConfig($this->config('https://example.com', ['rp_name' => 'Example admin']));

        self::assertSame('Example admin', $passkeyConfig->rpName());
    }

    /**
     * @param array<string, array|string|int|float|bool|null>|null $passkeys null = a published config without the key
     */
    private function config(string $appUrl, ?array $passkeys = null): Repository
    {
        $items = ['app' => ['url' => $appUrl, 'name' => 'Craftable']];
        if ($passkeys !== null) {
            $items['admin-auth'] = ['passkeys' => $passkeys];
        }

        return new Repository($items);
    }
}
