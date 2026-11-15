<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Config;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Illuminate\Contracts\Config\Repository as Config;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;

class TwoFactorConfigTest extends AdminUserTestCase
{
    private const string ROLES_ENV = 'ADMIN_TWO_FACTOR_REQUIRED_ROLES';
    private const string REQUIRED_ENV = 'ADMIN_TWO_FACTOR_REQUIRED';

    #[Override]
    protected function tearDown(): void
    {
        putenv(self::ROLES_ENV);
        putenv(self::REQUIRED_ENV);

        parent::tearDown();
    }

    public function testDefaultsMakeTwoFactorOptional(): void
    {
        $config = $this->app->make(Config::class);

        self::assertFalse($config->get('admin-auth.two_factor.required'));
        self::assertSame([], $config->get('admin-auth.two_factor.required_roles'));
        self::assertNull($config->get('admin-auth.two_factor.issuer'));
    }

    public function testRequiredIsReadFromTheEnv(): void
    {
        putenv(sprintf('%s=true', self::REQUIRED_ENV));

        self::assertTrue($this->loadTwoFactorConfig()['required']);
    }

    /**
     * @param array<int, string> $expected
     */
    #[DataProvider('provideRequiredRoles')]
    public function testRequiredRolesAreParsedFromTheEnv(string $env, array $expected): void
    {
        putenv(sprintf('%s=%s', self::ROLES_ENV, $env));

        self::assertSame($expected, $this->loadTwoFactorConfig()['required_roles']);
    }

    /**
     * @return iterable<string, array{string, array<int, string>}>
     */
    public static function provideRequiredRoles(): iterable
    {
        yield 'empty' => ['', []];
        yield 'one role' => ['Administrator', ['Administrator']];
        yield 'trimmed' => [' Administrator , Editor ', ['Administrator', 'Editor']];
        yield 'empty entries dropped' => ['Administrator,,Editor,', ['Administrator', 'Editor']];
    }

    /**
     * The package config file evaluated again, with the current env.
     *
     * @return array{required: bool, required_roles: array<int, string>, issuer: ?string}
     */
    private function loadTwoFactorConfig(): array
    {
        return (require __DIR__ . '/../../../config/admin-auth.php')['two_factor'];
    }
}
