<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Http\Controllers;

use Brackets\AdminAuth\Http\Requests\UpdatePasswordRequest;
use Brackets\AdminAuth\Http\Requests\UpdateProfileRequest;
use Brackets\AdminAuth\Services\ProfileService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ProfileController extends Controller
{
    private string $guard;

    public function __construct(
        private readonly Config $config,
        private readonly ProfileService $profileService,
        private readonly Redirector $redirector,
        private readonly UrlGenerator $urlGenerator,
        private readonly ViewFactory $viewFactory,
    ) {
        $this->guard = $this->config->get('admin-auth.defaults.guard', 'admin');
    }

    /**
     * Show the form for editing the logged-in admin's profile.
     */
    public function editProfile(Request $request): View
    {
        $adminUser = $this->getUser($request);

        return $this->viewFactory->make(
            'brackets/admin-auth::admin.profile.edit-profile',
            [
                'adminUser' => $adminUser,
                'action' => $this->urlGenerator->route('admin/update-profile'),
                'locales' => $this->profileService->getFallbackLocales(),
                'avatarCollection' => $this->profileService->getAvatarCollection($adminUser),
                'avatarMedia' => $this->profileService->getAvatarMedia($adminUser),
            ],
        );
    }

    /**
     * Update the logged-in admin's profile.
     *
     * @return array{redirect: string, message: string}|RedirectResponse
     */
    public function updateProfile(UpdateProfileRequest $request): array|RedirectResponse
    {
        $adminUser = $this->getUser($request);

        $adminUser->update($request->safe()->except('current_password'));

        if ($request->expectsJson()) {
            return [
                'redirect' => $this->urlGenerator->route('admin/edit-profile'),
                'message' => trans('brackets/admin-auth::admin.profile.saved'),
            ];
        }

        return $this->redirector->route('admin/edit-profile');
    }

    /**
     * Show the form for changing the logged-in admin's password.
     */
    public function editPassword(Request $request): View
    {
        $adminUser = $this->getUser($request);

        return $this->viewFactory->make(
            'brackets/admin-auth::admin.profile.edit-password',
            [
                'adminUser' => $adminUser,
                'action' => $this->urlGenerator->route('admin/update-password'),
            ],
        );
    }

    /**
     * Change the logged-in admin's password and log out their other sessions.
     *
     * @return array{redirect: string, message: string}|RedirectResponse
     */
    public function updatePassword(UpdatePasswordRequest $request): array|RedirectResponse
    {
        $adminUser = $this->getUser($request);
        $password = $request->validated('password');

        $this->profileService->changePassword($adminUser, $password);

        if ($request->expectsJson()) {
            return [
                'redirect' => $this->urlGenerator->route('admin/edit-password'),
                'message' => trans('brackets/admin-auth::admin.password_change.saved'),
            ];
        }

        return $this->redirector->route('admin/edit-password');
    }

    /**
     * @throws NotFoundHttpException
     */
    private function getUser(Request $request): Model&Authenticatable
    {
        $user = $request->user($this->guard);
        if (!$user instanceof Model || !$user instanceof Authenticatable) {
            throw NotFoundHttpException::fromStatusCode(404, trans('Admin User not found'));
        }

        return $user;
    }
}
