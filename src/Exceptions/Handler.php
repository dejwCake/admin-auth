<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Exceptions\Handler as ParentHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Override;

class Handler extends ParentHandler
{
    /**
     * @param Request $request
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    #[Override]
    protected function unauthenticated($request, AuthenticationException $exception,): JsonResponse|RedirectResponse
    {
        $url = str_starts_with($request->getRequestUri(), '/admin')
            ? $this->container->make(UrlGenerator::class)->route('brackets/admin-auth::admin/show-login-form')
            : $this->container->make(UrlGenerator::class)->route('login');

        return $this->shouldReturnJson($request, $exception)
            ? new JsonResponse(['message' => $exception->getMessage()], 401)
            : $this->container->make(Redirector::class)->guest($url);
    }
}
