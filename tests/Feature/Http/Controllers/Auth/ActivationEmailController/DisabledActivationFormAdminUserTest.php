<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\Auth\ActivationEmailController;

use Brackets\AdminAuth\Tests\AdminUserTestCase;

class DisabledActivationFormAdminUserTest extends AdminUserTestCase
{
    #[\Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('admin-auth.self_activation_form_enabled', false);
    }

    public function testCanNotSeeActivationFormIfDisabled(): void
    {
        $response = $this->get(url('/admin/activation'));
        $response->assertStatus(404);
    }
}
