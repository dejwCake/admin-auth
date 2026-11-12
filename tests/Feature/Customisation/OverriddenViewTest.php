<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Customisation;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Filesystem\Filesystem;
use Override;

use function Orchestra\Testbench\default_skeleton_path;

class OverriddenViewTest extends AdminUserTestCase
{
    private const string MARKER = 'Project profile override';

    #[Override]
    public function setUp(): void
    {
        // Laravel registers the vendor view path only if it exists when the view factory is resolved,
        // so the override has to be in place before the application boots.
        $filesystem = new Filesystem();
        $override = sprintf('%s/admin/profile/edit-profile.blade.php', self::overridePath());
        $filesystem->ensureDirectoryExists(dirname($override));
        $filesystem->put($override, self::MARKER);

        parent::setUp();
    }

    #[Override]
    public function tearDown(): void
    {
        (new Filesystem())->deleteDirectory(self::overridePath());

        parent::tearDown();
    }

    public function testOverriddenProfileViewIsRendered(): void
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);
        $user = TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertStatus(200);
        $response->assertSee(self::MARKER);
        $response->assertDontSee('<profile-form', false);
    }

    private static function overridePath(): string
    {
        return default_skeleton_path('resources/views/vendor/brackets/admin-auth');
    }
}
