<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

final class UpdatePasswordRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(Config $config): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                sprintf('current_password:%s', $config->get('admin-auth.defaults.guard', 'admin')),
            ],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
        ];
    }
}
