<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\PasskeyLoginController;

use Brackets\AdminAuth\Tests\UserTestCase;
use Illuminate\Database\DatabaseManager;

class OptionsUserTest extends UserTestCase
{
    public function testOptionsAreDiscoverableWithUserVerification(): void
    {
        $response = $this->postJson('/admin/login/passkey/options');

        $response->assertOk();
        self::assertSame('required', $response->json('userVerification'));
        self::assertSame('localhost', $response->json('rpId'));
        self::assertEmpty($response->json('allowCredentials') ?? []);
    }

    public function testOptionsAreNotFoundWithoutTheTable(): void
    {
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->postJson('/admin/login/passkey/options')->assertNotFound();
    }
}
