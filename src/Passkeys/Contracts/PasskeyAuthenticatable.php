<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys\Contracts;

use Brackets\AdminAuth\Models\AdminPasskey;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Webauthn\PublicKeyCredentialUserEntity;

interface PasskeyAuthenticatable extends Authenticatable
{
    /**
     * @return HasMany<AdminPasskey, covariant Model>
     */
    public function passkeys(): HasMany;

    public function webAuthnUserEntity(): PublicKeyCredentialUserEntity;
}
