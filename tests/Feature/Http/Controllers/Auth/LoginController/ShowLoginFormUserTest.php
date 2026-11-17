<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\LoginController;

use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Database\DatabaseManager;

class ShowLoginFormUserTest extends UserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testLoginPageOffersThePasskey(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(':passkey-options-url', false)
            ->assertSee('/admin/login/passkey/options', false)
            ->assertSee('/admin/login/passkey', false);
    }

    public function testLoginPageWithoutTheTableHasNoPasskeyOption(): void
    {
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');
        $this->createAdmin();

        $this->get('/admin/login')
            ->assertOk()
            ->assertDontSee(':passkey-options-url', false);
        $this->postJson('/admin/login', ['email' => 'john@example.com', 'password' => self::PASSWORD])
            ->assertNoContent();
    }
}
