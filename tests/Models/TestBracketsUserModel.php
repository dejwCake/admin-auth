<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Traits\CanActivate;
use Brackets\AdminAuth\Notifications\ActivationNotification;
use Brackets\AdminAuth\Notifications\ResetPassword;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $first_name
 * @property string $last_name
 */
class TestBracketsUserModel extends Authenticatable implements CanActivateContract
{
    use Notifiable;
    use CanActivate;
    use SoftDeletes;
    use HasRoles;

    /**
     * @var array<string>
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
    ];

    /**
     * @var array<string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @var array<string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var array<string>
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $appends = ['full_name', 'resource_url'];

    /**
     * @return array<class-string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => CarbonImmutable::class,
            'updated_at' => CarbonImmutable::class,
            'deleted_at' => CarbonImmutable::class,
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
