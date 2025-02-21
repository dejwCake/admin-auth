<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Console\Commands;

use Brackets\AdminAuth\Models\AdminUser;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Filesystem\Filesystem;

final class AdminAuthInstall extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $signature = 'admin-auth:install {--dont-install-admin-ui}';

    /**
     * The console command description.
     *
     * @var string
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.PropertyTypeHint.MissingNativeTypeHint
     */
    protected $description = 'Install a brackets/admin-auth package';

    public function __construct(private readonly Filesystem $filesystem, private readonly Config $config)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->info('Installing package brackets/admin-auth');

        if (!$this->option('dont-install-admin-ui')) {
            $this->call('admin-ui:install');
        }

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\AdminAuth\\AdminAuthServiceProvider",
        ]);

        $this->call('vendor:publish', [
            '--provider' => "Brackets\\AdminAuth\\Activation\\Providers\\ActivationServiceProvider",
        ]);

        $this->strReplaceInFile(
            resource_path('views/admin/layout/profile-dropdown.blade.php'),
            '{{-- Do not delete me :) I\'m used for auto-generation menu items --}}',
            '{{-- Do not delete me :) I\'m used for auto-generation menu items --}}
    <a href="{{ url(\'admin/logout\') }}" class="dropdown-item"><i class="fa fa-lock"></i> {{ trans(\'brackets/admin-auth::admin.profile_dropdown.logout\') }}</a>',
            '|url\(\'admin\/logout\'\)|',
        );

        $this->appendAdminAuthToAuthConfig();

        $this->call('migrate');

        $this->info('Package brackets/admin-auth installed');
    }

    private function strReplaceInFile(
        string $filePath,
        string $find,
        string $replaceWith,
        ?string $ifRegexNotExists = null,
    ): bool|int {
        $content = $this->filesystem->get($filePath);
        if ($ifRegexNotExists !== null && preg_match($ifRegexNotExists, $content)) {
            return false;
        }

        return $this->filesystem->put($filePath, str_replace($find, $replaceWith, $content));
    }

    /**
     * Append admin-auth config to auth config
     */
    private function appendAdminAuthToAuthConfig(): void
    {
        $auth = $this->config->get('auth');

        $this->strReplaceInFile(
            config_path('auth.php'),
            '\'guards\' => [',
            '\'guards\' => [
        \'admin\' => [
            \'driver\' => \'session\',
            \'provider\' => \'admin_users\',
        ],
        ',
            '|\'admin\' => \[|',
        );
        if (!isset($auth['guards'])) {
            $auth['guards'] = [];
        }
        $auth['guards']['admin'] = [
            'driver' => 'session',
            'provider' => 'admin_users',
        ];

        $this->strReplaceInFile(
            config_path('auth.php'),
            '\'providers\' => [',
            '\'providers\' => [
        \'admin_users\' => [
            \'driver\' => \'eloquent\',
            \'model\' => Brackets\AdminAuth\Models\AdminUser::class,
        ], 
        ',
            '|    \'providers\' => \[
        \'admin_users\' => \[|',
        );
        if (!isset($auth['providers'])) {
            $auth['providers'] = [];
        }
        $auth['providers']['admin_users'] = [
            'driver' => 'eloquent',
            'model' => AdminUser::class,
        ];

        $this->strReplaceInFile(
            config_path('auth.php'),
            '\'passwords\' => [',
            '\'passwords\' => [
        \'admin_users\' => [
            \'provider\' => \'admin_users\',
            \'table\' => \'admin_password_resets\',
            \'expire\' => 60,
        ],
        ',
            '|\'passwords\' => \[
        \'admin_users\' => \[|',
        );
        if (!isset($auth['passwords'])) {
            $auth['passwords'] = [];
        }
        $auth['passwords']['admin_users'] = [
            'provider' => 'admin_users',
            'table' => 'admin_password_resets',
            'expire' => 60,
        ];

        $this->config->set('auth', $auth);
    }
}
