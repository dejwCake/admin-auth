<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Models;

use Brackets\AdminAuth\Passkeys\Casts\CredentialRecordCast;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;
use Webauthn\CredentialRecord;

/**
 * @property int $id
 * @property int $admin_user_id
 * @property string $name
 * @property string $credential_id
 * @property CredentialRecord $credential
 * @property ?CarbonInterface $last_used_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Fillable(['name'])]
#[Hidden(['credential', 'credential_id'])]
#[Table('admin_passkeys')]
final class AdminPasskey extends Model
{
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
}
