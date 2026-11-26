<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\PasskeyController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Illuminate\Routing\Router;
use RuntimeException;

class UpdateTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testRenameOwnPasskey(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user, 'MacBook');

        $this->actingAsVerified($user)
            ->patchJson(sprintf('/admin/passkeys/%d', $passkey->id), ['name' => 'Work laptop'])
            ->assertOk()
            ->assertJsonPath('passkey.name', 'Work laptop');

        $this->get('/admin/passkeys')->assertSee('Work laptop');
    }

    public function testAnotherPackagesPasskeyBindingIsNotUsed(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user, 'MacBook');
        // laravel/passkeys binds the {passkey} route parameter to its own model for the whole app.
        $this->app->make(Router::class)->bind('passkey', static function (): never {
            throw new RuntimeException('Bound to another package\'s passkey.');
        });

        $this->actingAsVerified($user)
            ->patchJson(sprintf('/admin/passkeys/%d', $passkey->id), ['name' => 'Work laptop'])
            ->assertOk();
    }

    public function testNameIsRequired(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();
        $passkey = $this->addPasskey($user, 'MacBook');

        $this->actingAsVerified($user)
            ->patchJson(sprintf('/admin/passkeys/%d', $passkey->id), ['name' => ''])
            ->assertJsonValidationErrors('name');

        self::assertSame('MacBook', $passkey->fresh()?->name);
    }

    public function testAnotherAdminsPasskeyIsNotFound(): void
    {
        $this->allowAdmin();
        $other = $this->addPasskey($this->createAdmin('jane@example.com'), 'Jane phone');

        $this->actingAsVerified($this->createAdmin())
            ->patchJson(sprintf('/admin/passkeys/%d', $other->id), ['name' => 'Mine now'])
            ->assertNotFound();

        self::assertSame('Jane phone', $other->fresh()?->name);
    }

    public function testNonNumericIdIsNotFound(): void
    {
        $this->allowAdmin();
        $user = $this->createAdmin();

        // Not a database error on PostgreSQL, whose id column is an integer.
        $this->actingAsVerified($user)->patchJson('/admin/passkeys/abc', ['name' => 'Work laptop'])->assertNotFound();
    }
}
