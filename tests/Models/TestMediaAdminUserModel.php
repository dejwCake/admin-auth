<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Models;

use Brackets\Media\HasMedia\HasMediaCollectionsTrait;
use Brackets\Media\HasMedia\HasMediaThumbsTrait;
use Spatie\MediaLibrary\HasMedia;

/**
 * An admin model with an avatar collection, like craftable's AdminUser, without image conversions.
 */
final class TestMediaAdminUserModel extends TestAdminUserModel implements HasMedia
{
    use HasMediaCollectionsTrait;
    use HasMediaThumbsTrait;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->maxFilesize(5 * 1024 * 1024)
            ->maxNumberOfFiles(1);
    }
}
