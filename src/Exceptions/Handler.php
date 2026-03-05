<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Exceptions\Handler as ParentHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

class Handler extends ParentHandler
{
    public function __construct(
        Container $container,
        private readonly UrlGenerator $urlGenerator,
        private readonly Redirector $redirector,
    ) {
        parent::__construct($container);
    }

    /**
     * @param Request $request
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    protected function unauthenticated($request, AuthenticationException $exception,): JsonResponse|RedirectResponse
    {
        $url = str_starts_with($request->getRequestUri(), '/admin')
            ? $this->urlGenerator->route('brackets/admin-auth::admin/show-login-form')
            : $this->urlGenerator->route('login');

        return $this->shouldReturnJson($request, $exception)
            ? new JsonResponse(['message' => $exception->getMessage()], 401)
            : $this->redirector->guest($url);
    }
}
