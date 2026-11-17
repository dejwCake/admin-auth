<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Unit\Services\AdminLoginConditions;

use Brackets\AdminAuth\Services\AdminLoginConditions;
use Illuminate\Config\Repository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConditionsTest extends TestCase
{
    /**
     * @param array<string, bool> $expected
     */
    #[DataProvider('provideSettings')]
    public function testConditionsFollowTheSettings(
        bool $checkForbidden,
        bool $activationEnabled,
        array $expected,
    ): void {
        $adminLoginConditions = new AdminLoginConditions(new Repository(['admin-auth' => [
            'check_forbidden' => $checkForbidden,
            'activation_enabled' => $activationEnabled,
        ]]));

        self::assertSame($expected, $adminLoginConditions->conditions());
    }

    /**
     * @return iterable<string, array{bool, bool, array<string, bool>}>
     */
    public static function provideSettings(): iterable
    {
        yield 'none' => [false, false, []];
        yield 'forbidden only' => [true, false, ['forbidden' => false]];
        yield 'activation only' => [false, true, ['activated' => true]];
        yield 'both' => [true, true, ['forbidden' => false, 'activated' => true]];
    }
}
