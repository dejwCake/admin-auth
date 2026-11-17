<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Passkeys;

use Illuminate\Contracts\Config\Repository as Config;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;

/**
 * The relying party of every passkey ceremony, read from `admin-auth.passkeys` with defaults from `app.url`
 * and `app.name`.
 */
final readonly class PasskeyConfig
{
    public function __construct(private Config $config)
    {
    }

    /**
     * The domain passkeys are bound to; default the host of `app.url`.
     */
    public function rpId(): string
    {
        return (string) ($this->config->get('admin-auth.passkeys.rp_id') ?: ($this->appUrl()['host'] ?? 'localhost'));
    }

    public function rpName(): string
    {
        return (string) ($this->config->get('admin-auth.passkeys.rp_name') ?: $this->config->get(
            'app.name',
            'Laravel',
        ));
    }

    /**
     * Origins the admin is served on; default the scheme, host and port of `app.url`.
     *
     * @return array<int, string>
     */
    public function allowedOrigins(): array
    {
        // A published config without the key, or a value set at runtime, may not be a list yet.
        $origins = $this->config->get('admin-auth.passkeys.allowed_origins') ?? [];
        $origins = is_array($origins) ? $origins : explode(',', (string) $origins);
        $origins = array_values(array_filter(
            array_map(static fn (string|int $origin): string => rtrim(trim((string) $origin), '/'), $origins),
            static fn (string $origin): bool => $origin !== '',
        ));

        return $origins !== [] ? $origins : [$this->appOrigin()];
    }

    /**
     * The library's ceremony steps, checking the allowed origins.
     */
    public function ceremonyStepManagerFactory(): CeremonyStepManagerFactory
    {
        $ceremonyStepManagerFactory = new CeremonyStepManagerFactory();
        $ceremonyStepManagerFactory->setAllowedOrigins($this->allowedOrigins());

        return $ceremonyStepManagerFactory;
    }

    private function appOrigin(): string
    {
        $appUrl = $this->appUrl();
        $origin = sprintf('%s://%s', $appUrl['scheme'] ?? 'https', $appUrl['host'] ?? 'localhost');

        return isset($appUrl['port']) ? sprintf('%s:%d', $origin, $appUrl['port']) : $origin;
    }

    /**
     * @return array{scheme?: string, host?: string, port?: int}
     */
    private function appUrl(): array
    {
        $parsedUrl = parse_url((string) $this->config->get('app.url', ''));

        return is_array($parsedUrl) ? $parsedUrl : [];
    }
}
