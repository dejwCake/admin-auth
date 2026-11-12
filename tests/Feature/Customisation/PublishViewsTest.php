<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Customisation;

use Brackets\AdminAuth\AdminAuthServiceProvider;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Illuminate\Filesystem\Filesystem;
use Override;

class PublishViewsTest extends AdminUserTestCase
{
    private Filesystem $filesystem;

    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->filesystem = $this->app->make(Filesystem::class);
        $this->filesystem->deleteDirectory($this->publishedPath());
    }

    #[Override]
    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->publishedPath());

        parent::tearDown();
    }

    public function testViewsArePublishedWithThePackageLayout(): void
    {
        $this->publishViews();

        foreach (['admin/profile/edit-profile.blade.php', 'admin/auth/login.blade.php'] as $view) {
            self::assertFileEquals(
                sprintf('%s/../../../resources/views/%s', __DIR__, $view),
                sprintf('%s/%s', $this->publishedPath(), $view),
            );
        }
    }

    public function testExistingOverrideIsKeptWithoutForce(): void
    {
        $override = sprintf('%s/admin/profile/edit-profile.blade.php', $this->publishedPath());
        $this->filesystem->ensureDirectoryExists(dirname($override));
        $this->filesystem->put($override, 'project override');

        $this->publishViews();

        self::assertSame('project override', $this->filesystem->get($override));
    }

    private function publishViews(): void
    {
        $this->artisan('vendor:publish', [
            '--provider' => AdminAuthServiceProvider::class,
            '--tag' => 'views',
        ])->assertSuccessful();
    }

    private function publishedPath(): string
    {
        return $this->app->resourcePath('views/vendor/brackets/admin-auth');
    }
}
