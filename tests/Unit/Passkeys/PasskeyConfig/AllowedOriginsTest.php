<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Passkeys\PasskeyConfig;

use Brackets\AdminAuth\Passkeys\PasskeyConfig;
use Illuminate\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AllowedOriginsTest extends TestCase
{
    #[DataProvider('provideAppUrls')]
    public function testDefaultKeepsSchemeHostAndPortOfTheAppUrl(string $appUrl, string $origin): void
    {
        self::assertSame([$origin], (new PasskeyConfig($this->config($appUrl)))->allowedOrigins());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideAppUrls(): iterable
    {
        yield 'https' => ['https://admin.example.com', 'https://admin.example.com'];
        yield 'localhost with a port' => ['http://localhost:8080', 'http://localhost:8080'];
        yield 'path dropped' => ['https://example.com/app/', 'https://example.com'];
    }

    public function testCustomOriginsAreTrimmed(): void
    {
        $passkeyConfig = new PasskeyConfig($this->config('https://example.com', [
            'allowed_origins' => ['https://admin.example.com', ' https://example.com/ ', ''],
        ]));

        self::assertSame(['https://admin.example.com', 'https://example.com'], $passkeyConfig->allowedOrigins());
    }

    public function testOriginsGivenAsAString(): void
    {
        $passkeyConfig = new PasskeyConfig($this->config('https://example.com', [
            'allowed_origins' => 'https://admin.example.com,https://example.com',
        ]));

        self::assertSame(['https://admin.example.com', 'https://example.com'], $passkeyConfig->allowedOrigins());
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
