<?php

declare(strict_types=1);

return [
    'auth_global' => [
        'email' => 'Your e-mail',
        'password' => 'Password',
        'password_confirm' => 'Password confirmation',
    ],

    'login' => [
        'title' => 'Login',
        'sign_in_text' => 'Sign In to your account',
        'button' => 'Login',
        'forgot_password' => 'Forgot password?',
    ],

    'password_reset' => [
        'title' => 'Reset Password',
        'note' => 'Reset forgotten password',
        'button' => 'Reset password',
        'back_to_login' => 'Back to login',
    ],

    'forgot_password' => [
        'title' => 'Reset Password',
        'note' => 'Send password reset e-mail',
        'button' => 'Send Password Reset Link',
        'back_to_login' => 'Back to login',
    ],

    'activation_form' => [
        'title' => 'Activate account',
        'note' => 'Send activation link to e-mail',
        'button' => 'Send Activation Link',
    ],

    'activations' => [
        'sent' => 'We have sent you an activation link!',
        'activated' => 'Your account was activated!',
        'invalid_request' => 'The request failed.',
        'disabled' => 'Activation is disabled.',
        'invalid_token' => 'The activation token is invalid.',
        'invalid_user' => "We can't find a user with this e-mail address.",
    ],

    'passwords' => [
        'reset' => 'Your password has been reset!',
        'sent' => 'We have sent you a password reset link!',
        'invalid_password' => 'Password must be at least six characters long and match the confirmation.',
        'invalid_token' => 'The password reset token is invalid.',
        'invalid_user' => "We can't find a user with this e-mail address.",
    ],

    'profile_dropdown' => [
        'profile' => 'Profile',
        'password' => 'Password',
        'logout' => 'Logout',
    ],

    'profile' => [
        'title' => 'Edit profile',
        'columns' => [
            'first_name' => 'First name',
            'last_name' => 'Last name',
            'email' => 'E-mail',
            'language' => 'Language',
            'current_password' => 'Current password',
        ],
        'select_an_option' => 'Select an option',
        'saved' => 'Your profile has been saved.',
    ],

    'password_change' => [
        'title' => 'Change password',
        'columns' => [
            'current_password' => 'Current password',
            'password' => 'New password',
            'password_repeat' => 'New password confirmation',
        ],
        'saved' => 'Your password has been changed. Your other sessions have been logged out.',
    ],
];
