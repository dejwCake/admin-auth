<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\ProfileController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\Models\TestAdminUserModel;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class EditProfileAdminUserTest extends AdminUserTestCase
{
    public function testAdminOpensTheProfilePage(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertStatus(200);
        $response->assertSee('<profile-form', false);
        $response->assertSee('john@example.com');
        $response->assertSee('John');
        $response->assertDontSee('brackets/admin-auth::');
    }

    public function testGuestIsRedirectedToTheLogin(): void
    {
        $response = $this->get('/admin/profile');

        $response->assertRedirect(route('brackets/admin-auth::admin/show-login-form'));
    }

    public function testOnlyTheApplicationLocaleIsOfferedWithoutTranslatable(): void
    {
        $this->app['config']->set('app.locale', 'en');
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertSee(':language-options="[&quot;en&quot;]"', false);
    }

    public function testTranslatableLocalesAreOffered(): void
    {
        // What craftable-translatable's `*` view composer does with translatable.locales
        $this->app->make(ViewFactory::class)->composer('*', static function (View $view): void {
            $view->with('locales', new Collection(['en', 'sk']));
        });
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertSee(':language-options="[&quot;en&quot;,&quot;sk&quot;]"', false);
    }

    public function testModelWithoutAvatarCollectionGetsNoUploader(): void
    {
        $user = $this->createTestUser();

        $response = $this->actingAs($user, $this->adminAuthGuard)->get('/admin/profile');

        $response->assertDontSee(':media=', false);
    }

    private function createTestUser(): TestAdminUserModel
    {
        $this->app->make(Gate::class)->define('admin', static fn (): bool => true);

        return TestAdminUserModel::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => $this->app->make(Hasher::class)->make('testpass123'),
            'activated' => true,
            'forbidden' => false,
            'language' => 'en',
        ]);
    }
}
