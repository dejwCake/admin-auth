<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class UpdateProfileRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string|object>>
     */
    public function rules(Config $config): array
    {
        $guard = $config->get('admin-auth.defaults.guard', 'admin');
        $user = $this->user($guard);
        $user = $user instanceof Model ? $user : null;

        return [
            'first_name' => [
                'nullable',
                'string',
            ],
            'last_name' => [
                'nullable',
                'string',
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                $this->uniqueEmailRule($user),
            ],
            'language' => [
                'sometimes',
                'string',
            ],
            'current_password' => [
                Rule::requiredIf(fn (): bool => $this->changesEmail($user)),
                sprintf('current_password:%s', $guard),
            ],
        ];
    }

    private function uniqueEmailRule(?Model $user): Unique
    {
        if ($user === null) {
            return Rule::unique('admin_users', 'email');
        }

        $rule = Rule::unique($user::class, 'email')->ignore($user->getKey(), $user->getKeyName());

        // Soft-deleted admins don't block their e-mail
        if (method_exists($user, 'getDeletedAtColumn')) {
            $rule->whereNull($user->getDeletedAtColumn());
        }

        return $rule;
    }

    private function changesEmail(?Model $user): bool
    {
        if ($user === null || !$this->has('email')) {
            return false;
        }

        return $this->input('email') !== $user->getAttribute('email');
    }
}
