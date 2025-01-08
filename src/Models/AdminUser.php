<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Models;

use Brackets\AdminAuth\Activation\Contracts\CanActivate as CanActivateContract;
use Brackets\AdminAuth\Activation\Traits\CanActivate;
use Brackets\AdminAuth\Notifications\ResetPassword;
use Brackets\Media\HasMedia\AutoProcessMediaTrait;
use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Brackets\Media\HasMedia\HasMediaThumbsTrait;
use Brackets\Media\HasMedia\MediaCollection;
use Brackets\Media\HasMedia\ProcessMediaTrait;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $email
 * @property string $password
 * @property string $first_name
 * @property string $last_name
 * @property bool $activated
 * @property bool $forbidden
 * @property string $language
 * @property CarbonInterface $last_login_at
 */
class AdminUser extends Authenticatable implements CanActivateContract, HasMedia
{
    use Notifiable;
    use CanActivate;
    use SoftDeletes;
    use HasRoles;
    use AutoProcessMediaTrait;
    use HasMediaCollectionsTrait;
    use HasMediaThumbsTrait;
    use ProcessMediaTrait;

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
     * Get url of avatar image
     */
    public function getAvatarThumbUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'thumb_150') ?: null;
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

    /* ************************ MEDIA ************************ */

    /**
     * Register media collections
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->accepts('image/*');
    }

    /**
     * Register media conversions
     *
     * @phpcsSuppress SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->autoRegisterThumb200();

        $this->addMediaConversion('thumb_75')
            ->performOnCollections('avatar')
            ->nonQueued()
            ->width(75)
            ->height(75)
            ->fit(Fit::Crop, 75, 75)
            ->optimize();

        $this->addMediaConversion('thumb_150')
            ->performOnCollections('avatar')
            ->nonQueued()
            ->width(150)
            ->height(150)
            ->fit(Fit::Crop, 150, 150)
            ->optimize();
    }

    /**
     * Auto register thumb overridden
     */
    public function autoRegisterThumb200(): void
    {
        $this->getMediaCollections()->filter(
            static fn (MediaCollection $mediaCollection) => $mediaCollection->isImage(),
        )->each(
            function (MediaCollection $mediaCollection): void {
                $this->addMediaConversion('thumb_200')
                    ->performOnCollections($mediaCollection->getName())
                    ->nonQueued()
                    ->width(200)
                    ->height(200)
                    ->fit(Fit::Crop, 200, 200)
                    ->optimize();
            },
        );
    }

    /**
     * @return array<class-string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => CarbonImmutable::class,
            'updated_at' => CarbonImmutable::class,
            'deleted_at' => CarbonImmutable::class,
            'last_login_at' => CarbonImmutable::class,
        ];
    }
}
