<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Models;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Override;

/**
 * @property int $id
 * @property int $admin_user_id
 * @property string $secret
 * @property ?array<int, string> $recovery_codes hashes of the unused recovery codes
 * @property ?CarbonInterface $confirmed_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 */
#[Hidden(['secret', 'recovery_codes'])]
#[Table('admin_two_factors')]
final class AdminTwoFactor extends Model
{
    /**
     * @param Closure(string): bool $matches whether a stored hash is the given code's
     */
    public function consumeRecoveryCode(Closure $matches): ?self
    {
        return $this->getConnection()->transaction(function () use ($matches): ?self {
            $locked = $this->newQuery()->whereKey($this->getKey())->lockForUpdate()->first();
            if (!$locked instanceof self) {
                return null;
            }

            $hashes = array_values($locked->recovery_codes ?? []);
            foreach ($hashes as $index => $hash) {
                if ($matches($hash)) {
                    unset($hashes[$index]);
                    $locked->forceFill(['recovery_codes' => array_values($hashes)])->save();

                    return $locked;
                }
            }

            return null;
        });
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }
}
