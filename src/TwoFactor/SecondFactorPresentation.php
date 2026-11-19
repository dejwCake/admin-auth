<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\TwoFactor;

/**
 * The public data of one second factor's section on the two-factor settings page: a registered Vue
 * component that manages the factor in place, or a status with an optional link.
 */
final readonly class SecondFactorPresentation
{
    /**
     * @param array<string, array|string|int|float|bool|null> $props
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $icon,
        public bool $available,
        public bool $active,
        public string $status,
        public ?string $notice = null,
        public ?SecondFactorLink $link = null,
        public ?string $component = null,
        public array $props = [],
    ) {
    }

    /**
     * @return array{
     *     key: string,
     *     name: string,
     *     icon: string,
     *     available: bool,
     *     active: bool,
     *     status: string,
     *     notice: string|null,
     *     url: string|null,
     *     link_label: string|null,
     *     component: string|null,
     *     props: array<string, array|string|int|float|bool|null>,
     * }
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'icon' => $this->icon,
            'available' => $this->available,
            'active' => $this->active,
            'status' => $this->status,
            'notice' => $this->notice,
            'url' => $this->link?->url,
            'link_label' => $this->link?->label,
            'component' => $this->component,
            'props' => $this->props,
        ];
    }
}
