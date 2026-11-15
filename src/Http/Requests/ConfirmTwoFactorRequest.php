<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ConfirmTwoFactorRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
        ];
    }
}
