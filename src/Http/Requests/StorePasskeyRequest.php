<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The browser's answer to the creation options, with the name of the new passkey.
 */
final class StorePasskeyRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'credential' => ['required', 'array'],
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
