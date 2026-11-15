<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Console\Commands;

use Brackets\AdminAuth\Services\TwoFactorService;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Auth\AuthManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;

/**
 * Recovery path for an admin who lost both the device and the recovery codes.
 */
final class TwoFactorResetCommand extends Command
{
    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $signature = 'admin-auth:two-factor-reset
        {email : E-mail of the admin}
        {--force : Reset without asking for confirmation}';

    /**
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = 'Turn off two-factor authentication (secret, recovery codes, confirmation) for one admin';

    public function __construct(
        private readonly AuthManager $authManager,
        private readonly Config $config,
        private readonly TwoFactorService $twoFactorService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $guard = $this->config->get('admin-auth.defaults.guard', 'admin');
        $provider = $this->authManager->createUserProvider(
            $this->config->get(sprintf('auth.guards.%s.provider', $guard)),
        );

        // Through the guard's user provider, so a project's own admin model works too.
        $user = $provider?->retrieveByCredentials(['email' => $email]);
        if ($user === null) {
            $this->error(sprintf('No admin with the e-mail %s was found.', $email));

            return self::FAILURE;
        }

        if (
            !$user instanceof Model
            || !$user instanceof TwoFactorAuthenticatable
            || !$this->twoFactorService->isAvailableFor($user)
        ) {
            $this->error(trans('brackets/admin-auth::admin.two_factor.migration_missing'));

            return self::FAILURE;
        }

        if (!$user->hasTwoFactorData()) {
            $this->info(sprintf('%s has no two-factor authentication, nothing to reset.', $email));

            return self::SUCCESS;
        }

        if (
            !$this->option('force')
            && !$this->confirm(sprintf('Turn off two-factor authentication for %s?', $email), false)
        ) {
            $this->info('Nothing was changed.');

            return self::SUCCESS;
        }

        $this->twoFactorService->disable($user);

        $this->info(sprintf('Two-factor authentication for %s was turned off.', $email));

        return self::SUCCESS;
    }
}
