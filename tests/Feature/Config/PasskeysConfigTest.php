<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Config;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Illuminate\Contracts\Config\Repository as Config;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

class PasskeysConfigTest extends AdminUserTestCase
{
    private const string RP_ID_ENV = 'ADMIN_PASSKEYS_RP_ID';
    private const string RP_NAME_ENV = 'ADMIN_PASSKEYS_RP_NAME';
    private const string ORIGINS_ENV = 'ADMIN_PASSKEYS_ALLOWED_ORIGINS';

    #[Override]
    protected function tearDown(): void
    {
        putenv(self::RP_ID_ENV);
        putenv(self::RP_NAME_ENV);
        putenv(self::ORIGINS_ENV);

        parent::tearDown();
    }

    public function testDefaultsLeaveTheRelyingPartyToTheApp(): void
    {
        $config = $this->app->make(Config::class);

        self::assertNull($config->get('admin-auth.passkeys.rp_id'));
        self::assertNull($config->get('admin-auth.passkeys.rp_name'));
        self::assertSame([], $config->get('admin-auth.passkeys.allowed_origins'));
    }

    public function testRelyingPartyIsReadFromTheEnv(): void
    {
        putenv(sprintf('%s=example.com', self::RP_ID_ENV));
        putenv(sprintf('%s=Example admin', self::RP_NAME_ENV));

        $passkeys = $this->loadPasskeysConfig();

        self::assertSame('example.com', $passkeys['rp_id']);
        self::assertSame('Example admin', $passkeys['rp_name']);
    }

    /**
     * @param array<int, string> $expected
     */
    #[DataProvider('provideAllowedOrigins')]
    public function testAllowedOriginsAreParsedFromTheEnv(string $env, array $expected): void
    {
        putenv(sprintf('%s=%s', self::ORIGINS_ENV, $env));

        self::assertSame($expected, $this->loadPasskeysConfig()['allowed_origins']);
    }

    /**
     * @return iterable<string, array{string, array<int, string>}>
     */
    public static function provideAllowedOrigins(): iterable
    {
        yield 'empty' => ['', []];
        yield 'one origin' => ['https://admin.example.com', ['https://admin.example.com']];
        yield 'trimmed' => [
            ' https://admin.example.com , https://example.com ',
            ['https://admin.example.com', 'https://example.com'],
        ];

        yield 'empty entries dropped' => [
            'https://admin.example.com,,https://example.com,',
            ['https://admin.example.com', 'https://example.com'],
        ];
    }

    /**
     * The package config file evaluated again, with the current env.
     *
     * @return array{rp_id: ?string, rp_name: ?string, allowed_origins: array<int, string>}
     */
    private function loadPasskeysConfig(): array
    {
        return (require __DIR__ . '/../../../config/admin-auth.php')['passkeys'];
    }
}
