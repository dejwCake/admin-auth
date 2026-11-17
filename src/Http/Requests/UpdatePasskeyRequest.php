<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdatePasskeyRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
