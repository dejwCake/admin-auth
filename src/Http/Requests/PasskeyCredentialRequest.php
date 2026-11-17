<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * A browser's answer to passkey request options (login or two-factor challenge).
 */
final class PasskeyCredentialRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'credential' => ['required', 'array'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, array|string|int|float|bool|null>
     */
    public function credential(): array
    {
        return (array) $this->validated('credential');
    }
}
