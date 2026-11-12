<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\AdminAuthServiceProvider;
use Brackets\AdminAuth\Http\Controllers\ProfileController;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Orchestra\Testbench\Attributes\DefineEnvironment;

/**
 * How the package profile pages coexist with a profile generated into the application.
 */
class ProfileRoutesTest extends AdminUserTestCase
{
    private const array PROFILE_ROUTES = [
        'admin/edit-profile',
        'admin/update-profile',
        'admin/edit-password',
        'admin/update-password',
    ];

    public function testProfileIsAutoByDefault(): void
    {
        self::assertSame('auto', $this->app->make(Config::class)->get('admin-auth.profile.enabled'));
    }

    public function testPublishedConfigWithoutProfileBlockFallsBackToAuto(): void
    {
        $config = $this->app->make(Config::class);
        // A config/admin-auth.php published before the profile block existed
        $config->set('admin-auth', ['use_routes' => true]);

        // Registering the provider merges the package config under the published one.
        $this->app->register(AdminAuthServiceProvider::class, true);

        self::assertTrue($config->get('admin-auth.use_routes'));
        self::assertSame('auto', $config->get('admin-auth.profile.enabled'));
    }

    public function testAutoWithoutGeneratedProfileServesThePackagePage(): void
    {
        $router = $this->app->make(Router::class);
        foreach (self::PROFILE_ROUTES as $name) {
            self::assertTrue($router->has($name), $name);
        }
        self::assertSame(
            sprintf('%s@editProfile', ProfileController::class),
            $router->getRoutes()->getByName('admin/edit-profile')->getActionName(),
        );

        $this->actingAs($this->createTestUser(), $this->adminAuthGuard)
            ->get('/admin/profile')
            ->assertStatus(200)
            ->assertSee('<profile-form', false);
    }

    #[DefineEnvironment('defineGeneratedProfile')]
    public function testAutoWithGeneratedProfileServesTheApplicationPage(): void
    {
        $router = $this->app->make(Router::class);
        self::assertSame('Closure', $router->getRoutes()->getByName('admin/edit-profile')->getActionName());
        foreach (self::PROFILE_ROUTES as $name) {
            if ($name === 'admin/edit-profile') {
                continue;
            }
            self::assertFalse($router->has($name), $name);
        }

        $this->actingAs($this->createTestUser(), $this->adminAuthGuard)
            ->get('/admin/profile')
            ->assertStatus(200)
            ->assertSee('generated profile')
            ->assertDontSee('<profile-form', false);
    }

    #[DefineEnvironment('disableProfile')]
    public function testDisabledRegistersNoRoutes(): void
    {
        $this->assertNoProfileRoutes();
    }

    #[DefineEnvironment('disablePackageRoutes')]
    public function testPackageRoutesOffRegistersNoRoutesEvenWhenEnabled(): void
    {
        $this->assertNoProfileRoutes();
    }

    protected function defineGeneratedProfile(Application $app): void
    {
        $app['router']->middleware(['web'])
            ->get('/admin/profile', static fn (): string => 'generated profile')
            ->name('admin/edit-profile');
    }

    protected function disableProfile(Application $app): void
    {
        $app['config']->set('admin-auth.profile.enabled', false);
    }

    protected function disablePackageRoutes(Application $app): void
    {
        $app['config']->set('admin-auth.use_routes', false);
        $app['config']->set('admin-auth.profile.enabled', true);
    }

    private function assertNoProfileRoutes(): void
    {
        $router = $this->app->make(Router::class);
        foreach (self::PROFILE_ROUTES as $name) {
            self::assertFalse($router->has($name), $name);
        }
    }

    private function createTestUser(): TestAdminUserModel
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
        ]);
    }
}
