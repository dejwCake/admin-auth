<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Services\AdminLoginConditions;

use Brackets\AdminAuth\Services\AdminLoginConditions;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AllowsTest extends TestCase
{
    #[DataProvider('provideAdmins')]
    public function testAdminMustMeetEveryCondition(bool $activated, bool $forbidden, bool $expected): void
    {
        $user = $this->admin($activated, $forbidden);

        self::assertSame($expected, $this->conditions(true, true)->allows($user));
    }

    /**
     * @return iterable<string, array{bool, bool, bool}>
     */
    public static function provideAdmins(): iterable
    {
        yield 'active' => [true, false, true];
        yield 'not activated' => [false, false, false];
        yield 'forbidden' => [true, true, false];
    }

    public function testEveryAdminIsAllowedWithoutConditions(): void
    {
        $user = $this->admin(false, true);

        self::assertTrue($this->conditions(false, false)->allows($user));
    }

    public function testNonModelIsAllowedOnlyWithoutConditions(): void
    {
        $user = $this->createStub(Authenticatable::class);

        self::assertTrue($this->conditions(false, false)->allows($user));
        self::assertFalse($this->conditions(true, false)->allows($user));
    }

    private function admin(bool $activated, bool $forbidden): User
    {
        $user = new class extends User {
        };
        $user->forceFill(['activated' => $activated, 'forbidden' => $forbidden]);

        return $user;
    }

    private function conditions(bool $checkForbidden, bool $activationEnabled): AdminLoginConditions
    {
        return new AdminLoginConditions(new Repository(['admin-auth' => [
            'check_forbidden' => $checkForbidden,
            'activation_enabled' => $activationEnabled,
        ]]));
    }
}
