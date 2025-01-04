<?php

namespace Brackets\AdminAuth\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ParentHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class Handler extends ParentHandler
{
    /**
     * Convert an authentication exception into a response.
     *
     * @param Request $request
     * @param AuthenticationException $exception
     * @return JsonResponse|RedirectResponse
     */
    protected function unauthenticated($request, AuthenticationException $exception): JsonResponse|RedirectResponse
    {
        if (str_starts_with($request->getRequestUri(), '/admin')) {
            $url = route('brackets/admin-auth::admin/login');
        } else {
            $url = route('login');
        }

        return $this->shouldReturnJson($request, $exception)
            ? response()->json(['message' => $exception->getMessage()], 401)
            : redirect()->guest($url);
    }
}
