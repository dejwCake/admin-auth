<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Changes to an active two-factor setup (regenerating recovery codes, disabling) need the current password.
 */
final class TwoFactorPasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(Config $config): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                sprintf('current_password:%s', $config->get('admin-auth.defaults.guard', 'admin')),
            ],
        ];
    }
}
