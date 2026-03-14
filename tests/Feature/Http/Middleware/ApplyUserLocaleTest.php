<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Middleware;

use Brackets\AdminAuth\Http\Middleware\ApplyUserLocale;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

class ApplyUserLocaleTest extends AdminUserTestCase
{
    protected function createTestUser(string $language = 'en'): TestAdminUserModel
    {
        Notification::fake();

        return TestAdminUserModel::create([
            'email' => 'john@example.com',
            'password' => bcrypt('testpass123'),
            'activated' => true,
            'forbidden' => false,
            'language' => $language,
        ]);
    }

    public function testLocaleIsSetFromUserLanguage(): void
    {
        $user = $this->createTestUser('sk');

        Gate::define('admin', static fn (): bool => true);

        self::assertEquals('en', $this->app->getLocale());

        $this->actingAs($user, $this->adminAuthGuard)->get('/admin');

        self::assertEquals('sk', $this->app->getLocale());
    }

    public function testLocaleIsNotChangedForGuest(): void
    {
        $defaultLocale = $this->app->getLocale();

        $middleware = $this->app->make(ApplyUserLocale::class);
        $request = Request::create('/admin/login', 'GET');

        $middleware->handle($request, static fn () => null);

        self::assertEquals($defaultLocale, $this->app->getLocale());
    }

    public function testDefaultLocaleUsedWhenUserLanguageMatchesDefault(): void
    {
        $defaultLocale = $this->app->getLocale();
        $user = $this->createTestUser($defaultLocale);

        $middleware = $this->app->make(ApplyUserLocale::class);

        $this->actingAs($user, $this->adminAuthGuard);

        $request = Request::create('/admin', 'GET');
        $middleware->handle($request, static fn () => null);

        self::assertEquals($defaultLocale, $this->app->getLocale());
    }
}
