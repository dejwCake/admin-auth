<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys\Traits;

use Brackets\AdminAuth\Models\AdminPasskey;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webauthn\PublicKeyCredentialUserEntity;

trait HasPasskeys
{
    /**
     * On the fixed key admin_user_id, not one derived from the model class, so every admin model shares the table.
     *
     * @return HasMany<AdminPasskey, $this>
     */
    public function passkeys(): HasMany
    {
        return $this->hasMany(AdminPasskey::class, 'admin_user_id');
    }

    public function webAuthnUserEntity(): PublicKeyCredentialUserEntity
    {
        $email = (string) $this->getAttribute('email');
        $displayName = trim((string) ($this->getAttribute('full_name') ?? $this->getAttribute('name') ?? ''));

        return PublicKeyCredentialUserEntity::create(
            $email,
            (string) $this->getKey(),
            $displayName !== '' ? $displayName : $email,
        );
    }
}
