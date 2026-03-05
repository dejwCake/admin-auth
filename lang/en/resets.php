<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Reset Passwords Lines
    |--------------------------------------------------------------------------
    |
    */

    'email' => [
        'greeting' => 'Hello!',
        'line' => 'You are receiving this email because we received a password reset request for your account.',
        'action' => 'Reset Password',
        'notRequested' => 'If you did not request a password reset, no further action is required.',
        'salutation' => 'Regards',
        'subcopy' => 'If you\'re having trouble clicking the ":actionText" button, '
            . 'copy and paste the URL below into your web browser: [:actionUrl](:actionUrl)',
    ],
];
