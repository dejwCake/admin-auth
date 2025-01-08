<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Traits\CanActivate;
use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Notifications\ResetPassword;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $email
 * @property string $password
 * @property string $first_name
 * @property string $last_name
 * @property bool $activated
 * @property bool $forbidden
 * @property string $language
 * @property ?CarbonInterface $last_login_at
 */
class TestBracketsUserModel extends Authenticatable implements CanActivateContract
{
    use Notifiable;
    use CanActivate;
    use SoftDeletes;
    use HasRoles;

    /**
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'activated',
        'forbidden',
        'language',
        'last_login_at',
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
     * @var array<int, string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $appends = ['full_name', 'resource_url'];

    /**
     * @return array<string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'date:' . CarbonInterface::DEFAULT_TO_STRING_FORMAT,
            'updated_at' => 'date:' . CarbonInterface::DEFAULT_TO_STRING_FORMAT,
            'deleted_at' => 'date:' . CarbonInterface::DEFAULT_TO_STRING_FORMAT,
            'last_login_at' => 'date:' . CarbonInterface::DEFAULT_TO_STRING_FORMAT,
        ];
    }

    /**
     * Resource url to generate edit
     */
    public function getResourceUrlAttribute(): string
    {
        return url('/admin/admin-users/' . $this->getKey());
    }

    /**
     * Full name for admin user
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

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

    /**
     * Send the password reset notification.
     */
    public function sendActivationNotification(string $token): void
    {
        $this->notify(app(ActivationNotification::class, ['token' => $token]));
    }
}
