<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Models;

use Brackets\AdminAuth\Passkeys\Casts\CredentialRecordCast;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Override;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;

/**
 * @property int $id
 * @property int $admin_user_id
 * @property string $name
 * @property string $credential_id
 * @property string $credential_id_hash
 * @property CredentialRecord $credential
 * @property ?CarbonInterface $last_used_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['name'])]
#[Hidden(['credential', 'credential_id', 'credential_id_hash'])]
#[Table('admin_passkeys')]
final class AdminPasskey extends Model
{
    /**
     * The stored form of a credential ID: unpadded base64url, as browsers send `rawId`.
     */
    public static function encodeCredentialId(string $rawCredentialId): string
    {
        return Base64UrlSafe::encodeUnpadded($rawCredentialId);
    }

    /**
     * A credential ID can be longer than an index allows, so it is unique and looked up by its hash, which is
     * set with it.
     */
    public static function hashCredentialId(string $credentialId): string
    {
        return hash('sha256', $credentialId);
    }

    /**
     * Save, unless the credential is stored already (by a parallel request since the caller checked).
     */
    public function saveUnlessDuplicate(): bool
    {
        try {
            return $this->save();
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'credential' => CredentialRecordCast::class,
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string, string>
     */
    protected function credentialId(): Attribute
    {
        return Attribute::make(set: static fn (string $value): array => [
            'credential_id' => $value,
            'credential_id_hash' => self::hashCredentialId($value),
        ]);
    }
}
