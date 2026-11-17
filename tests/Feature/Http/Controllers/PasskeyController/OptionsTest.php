<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\PasskeyController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\DataProvider;

class OptionsTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testCorrectPasswordIssuesTheOptions(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        $response = $this->actingAsVerified($user)
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD]);

        $response->assertOk();
        self::assertSame('localhost', $response->json('rp.id'));
        self::assertSame('john@example.com', $response->json('user.name'));
        self::assertSame('required', $response->json('authenticatorSelection.residentKey'));
    }

    public function testCustomRelyingPartyIsNamed(): void
    {
        $this->allowAdmin();
        $this->app['config']->set('admin-auth.passkeys.rp_id', 'example.com');
        $this->app['config']->set('admin-auth.passkeys.allowed_origins', ['https://admin.example.com']);

        $this->actingAsVerified($this->createAdmin())
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])
            ->assertOk()
            ->assertJsonPath('rp.id', 'example.com');
    }

    /**
     * @return iterable<string, array{array<string, string>}>
     */
    public static function provideWrongPasswords(): iterable
    {
        yield 'wrong' => [['current_password' => 'wrong-password']];
        yield 'missing' => [[]];
    }

    /**
     * @param array<string, string> $data
     */
    #[DataProvider('provideWrongPasswords')]
    public function testWrongPasswordIssuesNoOptions(array $data): void
    {
        $this->allowAdmin();

        $this->actingAsVerified($this->createAdmin())
            ->postJson('/admin/passkeys/options', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_password')
            ->assertJsonMissingPath('challenge');
        self::assertNull($this->app->make(Session::class)->get('admin-auth.passkeys.register'));
    }

    public function testMissingTableIsAConflict(): void
    {
        $this->allowAdmin();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->actingAsVerified($this->createAdmin())
            ->postJson('/admin/passkeys/options', ['current_password' => self::PASSWORD])
            ->assertConflict();
    }
}
