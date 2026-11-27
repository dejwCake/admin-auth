<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

/**
 * Admin on its own connection, not the default one.
 */
class TestConnectionAdminUserModel extends TestAdminUserModel
{
    public const string CONNECTION = 'admins';

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $connection = self::CONNECTION;
}
