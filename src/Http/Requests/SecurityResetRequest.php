<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Requests;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Resetting another admin's second factors: the permission, the acting admin's password, never themselves.
 */
final class SecurityResetRequest extends FormRequest
{
    public const string PERMISSION = 'admin.admin-user.security-reset';

    public function authorize(Gate $gate, Config $config, AuthFactory $authFactory): bool
    {
        $user = $authFactory->guard($config->get('admin-auth.defaults.guard', 'admin'))->user();

        return $user !== null && $gate->forUser($user)->allows(self::PERMISSION);
    }

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

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(Config $config, AuthFactory $authFactory): array
    {
        $user = $authFactory->guard($config->get('admin-auth.defaults.guard', 'admin'))->user();

        return [
            function (Validator $validator) use ($user): void {
                if ($user !== null && $this->isTarget($user)) {
                    $validator->errors()->add(
                        'admin_user',
                        trans('brackets/admin-auth::admin.security_reset.own_account'),
                    );
                }
            },
        ];
    }

    /**
     * Whether the admin is the one the reset is for.
     */
    private function isTarget(Authenticatable $user): bool
    {
        return (string) $user->getAuthIdentifier() === (string) $this->route('targetAdminId');
    }
}
