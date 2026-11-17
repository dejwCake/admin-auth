<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Passkeys\PasskeyConfig;

use Brackets\AdminAuth\Passkeys\PasskeyConfig;
use Illuminate\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RpIdTest extends TestCase
{
    #[DataProvider('provideAppUrls')]
    public function testDefaultIsTheHostOfTheAppUrl(string $appUrl, string $rpId): void
    {
        self::assertSame($rpId, (new PasskeyConfig($this->config($appUrl)))->rpId());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAppUrls(): iterable
    {
        yield 'https' => ['https://admin.example.com', 'admin.example.com'];
        yield 'localhost with a port' => ['http://localhost:8080', 'localhost'];
        yield 'path dropped' => ['https://example.com/app/', 'example.com'];
    }

    public function testCustomRelyingParty(): void
    {
        $passkeyConfig = new PasskeyConfig($this->config('https://admin.example.com', ['rp_id' => 'example.com']));

        self::assertSame('example.com', $passkeyConfig->rpId());
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
