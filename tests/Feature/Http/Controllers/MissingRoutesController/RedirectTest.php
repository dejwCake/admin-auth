<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Tests\Feature\Http\Controllers\MissingRoutesController;

use Brackets\AdminAuth\Http\Controllers\MissingRoutesController;
use Brackets\AdminAuth\Tests\AdminUserTestCase;
use Illuminate\Routing\Redirector;

class RedirectTest extends AdminUserTestCase
{
    public function testRedirectsToAdminLogin(): void
    {
        $redirector = $this->app->make(Redirector::class);
        $controller = new MissingRoutesController();

        $response = $controller->redirect($redirector);

        self::assertEquals(302, $response->getStatusCode());
        self::assertStringContainsString('/admin/login', $response->getTargetUrl());
    }
}
