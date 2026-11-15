<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class TwoFactorChallengeRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'required_without:recovery_code'],
            'recovery_code' => ['nullable', 'string'],
        ];
    }
}
