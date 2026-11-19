<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

use Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor;
use Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor;
use Brackets\AdminAuth\TwoFactor\Factors\TotpFactor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

/**
 * The second factors listed in `admin-auth.two_factor.factors`, in that order.
 */
final readonly class SecondFactorRegistry
{
    /**
     * Used when the key is missing, e.g. from a published config with its own `two_factor` array.
     */
    public const array DEFAULT_FACTORS = [TotpFactor::class, PasskeyFactor::class];

    public function __construct(private Config $config, private Container $container)
    {
    }

    /**
     * @return array<int, SecondFactor>
     * @throws InvalidArgumentException for a class that does not exist or is not a second factor
     */
    public function all(): array
    {
        $classes = $this->config->get('admin-auth.two_factor.factors', self::DEFAULT_FACTORS);

        return array_map(
            fn (mixed $class): SecondFactor => $this->resolve($class),
            array_values(is_array($classes) ? $classes : self::DEFAULT_FACTORS),
        );
    }

    /**
     * The settings page's sections, as `SecondFactorPresentation::toArray()`.
     *
     * @return array<int, array<string, array|string|bool|null>>
     */
    public function presentFor(Authenticatable $user): array
    {
        return array_map(
            static fn (SecondFactor $factor): array => $factor->present($user)->toArray(),
            $this->all(),
        );
    }

    /**
     * The available factors that are active for the admin: two-factor is on when this is not empty.
     *
     * @return array<int, SecondFactor>
     */
    public function activeFor(Authenticatable $user): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (SecondFactor $factor): bool => $factor->isAvailableFor($user) && $factor->isActiveFor($user),
        ));
    }

    private function resolve(mixed $class): SecondFactor
    {
        if (!is_string($class) || !class_exists($class) || !is_subclass_of($class, SecondFactor::class)) {
            throw new InvalidArgumentException(sprintf(
                'admin-auth.two_factor.factors lists %s, which is not a %s class.',
                is_string($class) ? $class : get_debug_type($class),
                SecondFactor::class,
            ));
        }

        return $this->container->make($class);
    }
}
