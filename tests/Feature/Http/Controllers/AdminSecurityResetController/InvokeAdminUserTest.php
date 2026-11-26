<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\AdminSecurityResetController;

use Brackets\AdminAuth\Http\Requests\SecurityResetRequest;
use Brackets\AdminAuth\Notifications\SecurityResetNotification;
use Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Brackets\AdminAuth\Tests\PasskeyHelpers;
use Brackets\AdminAuth\Tests\RecordingLogger;
use Brackets\AdminAuth\Tests\TwoFactorHelpers;
use Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Psr\Log\LoggerInterface;
use RuntimeException;

class InvokeAdminUserTest extends AdminUserTestCase
{
    use PasskeyHelpers;
    use TwoFactorHelpers;

    public function testResetRemovesTotpAndPasskeys(): void
    {
        Notification::fake();
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->enrol($target);
        $this->addPasskey($target, 'MacBook');
        $this->addPasskey($target, 'YubiKey');

        $this->reset($actor, $target)
            ->assertOk()
            ->assertJson(['message' => trans('brackets/admin-auth::admin.security_reset.done')]);

        self::assertFalse($target->fresh()->hasTwoFactorData());
        self::assertSame(0, $target->passkeys()->count());

        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => 'jane@example.com', 'password' => self::PASSWORD]);
        $this->get('/admin')->assertOk();
    }

    public function testForcedTargetEnrolsAgain(): void
    {
        Notification::fake();
        $this->requireTwoFactor();
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->enrol($target);

        $this->reset($actor, $target)->assertOk();

        $this->post('/admin/logout');
        $this->post('/admin/login', ['email' => 'jane@example.com', 'password' => self::PASSWORD]);
        $this->get('/admin')->assertRedirect(route('brackets/admin-auth::admin/two-factor'));
    }

    public function testNothingToResetSendsNothing(): void
    {
        Notification::fake();
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');

        $this->reset($actor, $target)
            ->assertOk()
            ->assertJson(['message' => trans('brackets/admin-auth::admin.security_reset.nothing_to_reset')]);

        Notification::assertNothingSent();
    }

    public function testUnknownAdminIsNotFound(): void
    {
        $actor = $this->createActor();

        $this->actingAsVerified($actor)
            ->postJson('/admin/admin-users/999999/security-reset', ['current_password' => self::PASSWORD])
            ->assertNotFound();
    }

    public function testNonNumericIdIsNotFound(): void
    {
        $actor = $this->createActor();

        $this->actingAsVerified($actor)
            ->postJson('/admin/admin-users/abc/security-reset', ['current_password' => self::PASSWORD])
            ->assertNotFound();
    }

    public function testMissingMigrationsResetNothingWithoutAnError(): void
    {
        Notification::fake();
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->dropTwoFactorTable();
        $this->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->dropIfExists('admin_passkeys');

        $this->reset($actor, $target)
            ->assertOk()
            ->assertJson(['message' => trans('brackets/admin-auth::admin.security_reset.nothing_to_reset')]);
    }

    public function testAnotherBindingOfTheAdminUserParameterIsNotUsed(): void
    {
        Notification::fake();
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);
        // Generated apps often bind {adminUser} to their model for the whole app.
        $this->app->make(Router::class)->bind('adminUser', static function (): never {
            throw new RuntimeException('Bound to the project\'s admin user.');
        });

        $this->reset($actor, $target)->assertOk();

        self::assertSame(0, $target->passkeys()->count());
    }

    public function testWithoutThePermissionIsForbidden(): void
    {
        $this->allowAdmin();
        $actor = $this->createAdmin();
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);

        $this->reset($actor, $target)->assertForbidden();

        self::assertSame(1, $target->passkeys()->count());
    }

    public function testWrongPasswordChangesNothing(): void
    {
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);

        $this->reset($actor, $target, 'wrong-password')->assertJsonValidationErrors('current_password');
        $this->reset($actor, $target, '')->assertJsonValidationErrors('current_password');

        self::assertSame(1, $target->passkeys()->count());
    }

    public function testOwnAccountIsRefused(): void
    {
        $actor = $this->createActor();
        $this->addPasskey($actor);

        $this->reset($actor, $actor)
            ->assertJsonValidationErrors(
                ['admin_user' => trans('brackets/admin-auth::admin.security_reset.own_account')],
            );

        self::assertSame(1, $actor->passkeys()->count());
    }

    public function testUnverifiedSessionIsRefusedByTheGate(): void
    {
        $actor = $this->createActor();
        $this->enrol($actor);
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);

        $this->actingAs($actor, $this->adminAuthGuard)
            ->postJson(sprintf('/admin/admin-users/%s/security-reset', $target->getKey()), [
                'current_password' => self::PASSWORD,
            ])
            ->assertForbidden()
            ->assertJson(['redirect' => route('brackets/admin-auth::admin/two-factor/challenge')]);

        self::assertSame(1, $target->passkeys()->count());
    }

    public function testTargetIsNotifiedAndTheResetLogged(): void
    {
        Notification::fake();
        $logger = new RecordingLogger();
        $this->app->instance(LoggerInterface::class, $logger);
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);

        $this->reset($actor, $target)->assertOk();

        Notification::assertSentTo($target, SecurityResetNotification::class, static function (
            SecurityResetNotification $notification,
        ) use ($target): bool {
            $lines = implode(' ', $notification->toMail($target)->introLines);

            return str_contains($lines, 'john@example.com');
        });
        Notification::assertCount(1);
        $context = $logger->context('info', 'Second factors of an admin were reset by another admin.');
        self::assertNotNull($context);
        self::assertSame($actor->getKey(), $context['actor_id']);
        self::assertSame($target->getKey(), $context['target_id']);
        self::assertSame(1, $context['passkeys']);
    }

    public function testFailingEmailStillReportsTheResetAndLogsIt(): void
    {
        $logger = new RecordingLogger();
        $this->app->instance(LoggerInterface::class, $logger);
        // An undefined mailer: sending the e-mail throws.
        $this->app['config']->set('mail.default', 'undefined');
        $actor = $this->createActor();
        $target = $this->createAdmin('jane@example.com');
        $this->addPasskey($target);

        $this->reset($actor, $target)->assertOk();

        self::assertSame(0, $target->passkeys()->count());
        self::assertNotNull($logger->context('info', 'Second factors of an admin were reset by another admin.'));
        self::assertNotNull($logger->context('error', 'The security reset e-mail could not be sent.'));
    }

    /**
     * The acting admin, allowed to reset others.
     */
    private function createActor(): Model&TwoFactorAuthenticatable&PasskeyAuthenticatable
    {
        $this->allowAdmin();
        $actor = $this->createAdmin();
        $this->app->make(Gate::class)->define(
            SecurityResetRequest::PERMISSION,
            static fn (Model $user): bool => $user->getKey() === $actor->getKey(),
        );

        return $actor;
    }

    private function reset(
        Model&TwoFactorAuthenticatable&PasskeyAuthenticatable $actor,
        Model $target,
        string $password = self::PASSWORD,
    ): TestResponse {
        return $this->actingAsVerified($actor)
            ->postJson(sprintf('/admin/admin-users/%s/security-reset', $target->getKey()), [
                'current_password' => $password,
            ]);
    }
}
