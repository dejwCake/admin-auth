<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

/**
 * Admin with roles held in memory, so role checks need no permission tables.
 */
class TestRoleAdminUserModel extends TestAdminUserModel
{
    /** @var array<int, string> */
    private array $testRoles = [];

    /**
     * @param array<int, string> $roles
     */
    public function setTestRoles(array $roles): self
    {
        $this->testRoles = $roles;

        return $this;
    }

    /**
     * @param array<int, string>|string ...$roles
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function hasAnyRole(...$roles): bool
    {
        $names = array_merge(...array_map(static fn (array|string $role): array => (array) $role, $roles));

        return array_intersect($names, $this->testRoles) !== [];
    }
}
