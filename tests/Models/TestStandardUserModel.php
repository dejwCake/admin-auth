<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Traits\CanActivate;
use Brackets\AdminAuth\Notifications\ResetPassword;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property string $name
 * @property string $email
 * @property string $password
 */
class TestStandardUserModel extends Authenticatable implements CanActivateContract
{
    use Notifiable;
    use CanActivate;

    /**
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Send the password reset notification.
     *
     * @param string $token
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(app(ResetPassword::class, ['token' => $token]));
    }
}
