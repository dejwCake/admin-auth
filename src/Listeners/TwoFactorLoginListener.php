<?php

declare(strict_types=1);

namespace Brackets\AdminAuth\Listeners;

use Brackets\AdminAuth\TwoFactor\TwoFactorSession;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Http\Request;

/**
 * Keeps a two-factor-verified session verified when the admin guard logs another admin in it
 * (impersonal login). The password login clears both keys afterwards, so it keeps its own handling.
 */
final readonly class TwoFactorLoginListener
{
    public function __construct(
        private Config $config,
        private Request $request,
        private TwoFactorSession $twoFactorSession,
    ) {
    }

    public function handle(Login $event): void
    {
        if (
            $event->guard !== $this->config->get('admin-auth.defaults.guard', 'admin')
            || !$this->request->hasSession()
        ) {
            return;
        }

        $this->twoFactorSession->handOver($this->request->session(), $event->user);
    }
}
