<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Services;

use Brackets\Media\HasMedia\MediaCollection;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * What the profile and password pages need beyond reading and saving the admin's attributes.
 */
final readonly class ProfileService
{
    private const string AVATAR_COLLECTION = 'avatar';

    public function __construct(private AuthFactory $authFactory, private Config $config, private Hasher $hasher,)
    {
    }

    /**
     * Language options when craftable-translatable isn't installed. Its `*` view composer replaces them with
     * translatable.locales whenever it is.
     */
    public function getFallbackLocales(): Collection
    {
        return new Collection([$this->config->get('app.locale')]);
    }

    /**
     * The avatar collection, when the admin model uses craftable-media's collections.
     */
    public function getAvatarCollection(Model $adminUser): ?MediaCollection
    {
        if (!method_exists($adminUser, 'getCustomMediaCollection')) {
            return null;
        }

        $collection = $adminUser->getCustomMediaCollection(self::AVATAR_COLLECTION);

        return $collection instanceof MediaCollection ? $collection : null;
    }

    public function getAvatarMedia(Model $adminUser): Collection
    {
        if (
            $this->getAvatarCollection($adminUser) === null
            || !method_exists($adminUser, 'getThumbs200ForCollection')
        ) {
            return new Collection();
        }

        return $adminUser->getThumbs200ForCollection(self::AVATAR_COLLECTION);
    }

    /**
     * Stores the new password and ends the admin's other sessions.
     */
    public function changePassword(Model&Authenticatable $adminUser, string $password): void
    {
        $adminUser->forceFill([
            $adminUser->getAuthPasswordName() => $this->hasher->make($password),
        ])->save();

        // Fires OtherDeviceLogout and refreshes this device's remember-me cookie. The AuthenticateSession
        // middleware then logs out every other session, as their stored password hash no longer matches.
        $guard = $this->authFactory->guard($this->config->get('admin-auth.defaults.guard', 'admin'));
        if ($guard instanceof SessionGuard) {
            $guard->logoutOtherDevices($password);
        }
    }
}
