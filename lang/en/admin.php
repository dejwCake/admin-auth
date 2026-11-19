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
        'passkey_button' => 'Sign in with a passkey',
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
        'two_factor' => 'Two-factor authentication',
        'passkeys' => 'Passkeys',
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

    'two_factor' => [
        'title' => 'Two-factor authentication',
        'status_on' => 'On',
        'status_off' => 'Off',
        'enable' => 'Enable two-factor authentication',
        'enable_description' => 'After the password, you will also enter a code from an authenticator app.',
        'scan_qr' => 'Scan this QR code with your authenticator app, then enter the code it shows.',
        'secret_label' => 'Or enter this key manually:',
        'code' => 'Code',
        'confirm' => 'Confirm',
        'recovery_codes_title' => 'Recovery codes',
        'recovery_codes_description' => 'Store these single-use codes safely. They will not be shown again.',
        'copy' => 'Copy',
        'copied' => 'Copied',
        'download' => 'Download',
        'done' => 'Done',
        'current_password' => 'Current password',
        'regenerate' => 'Regenerate recovery codes',
        'disable' => 'Disable two-factor authentication',
        'required_notice' => 'Two-factor authentication is required. Turn off the code only while you have a passkey.',
        'enrolment_required' => 'Two-factor authentication is required for your account. Enable it to continue.',
        'challenge_required' => 'Confirm your login with a two-factor authentication code.',
        'migration_missing' => 'Two-factor needs a migration: publish the admin-auth migrations, then run migrate.',
        'already_enabled' => 'Two-factor authentication is already enabled.',
        'not_enabled' => 'Two-factor authentication is not enabled.',
        'cannot_disable_required' => 'Two-factor is required. Add a passkey before you turn off the code.',
        'invalid_code' => 'The code is invalid.',
        'invalid_recovery_code' => 'The recovery code is invalid.',
        'enabled' => 'Two-factor authentication has been enabled.',
        'disabled' => 'Two-factor authentication has been disabled.',
        'recovery_codes_regenerated' => 'New recovery codes have been generated.',
        'passkeys_hint' => 'You can also sign in and confirm your login with a passkey:',
        'challenge' => [
            'title' => 'Two-factor authentication',
            'code_text' => 'Enter the code from your authenticator app.',
            'recovery_code_text' => 'Enter one of your recovery codes.',
            'code' => 'Code',
            'recovery_code' => 'Recovery code',
            'button' => 'Verify',
            'use_recovery_code' => 'Use a recovery code',
            'use_code' => 'Use an authentication code',
            'passkey_text' => 'Confirm your login with your passkey.',
            'use_passkey' => 'Use a passkey',
            'or' => 'or',
        ],
    ],

    'passkeys' => [
        'title' => 'Passkeys',
        'description' => 'Sign in with your fingerprint, face or device PIN, or use it as the second factor.',
        'empty' => 'You have no passkeys yet.',
        'add' => 'Add a passkey',
        'name' => 'Name',
        'default_name' => ':browser on :os',
        'current_password' => 'Current password',
        'rename' => 'Rename',
        'remove' => 'Remove',
        'remove_confirm' => 'Enter your password to remove this passkey.',
        'added_at' => 'Added',
        'last_used' => 'last used',
        'never_used' => 'never',
        'unsupported' => 'Passkeys don\'t work here: they need a current browser and a secure connection (HTTPS).',
        'migration_missing' => 'Passkeys need a migration: publish the admin-auth migrations, then run migrate.',
        'invalid' => 'The passkey could not be verified. Please try again.',
        'cannot_remove_last' => 'Two-factor authentication is required and this is your last second factor.',
        'added' => 'The passkey has been added.',
        'renamed' => 'The passkey has been renamed.',
        'removed' => 'The passkey has been removed.',
    ],

    'security_reset' => [
        'reset_btn' => 'Reset two-factor',
        'confirm_title' => 'Reset two-factor authentication?',
        'confirm_text' => 'This removes the admin\'s authenticator app, recovery codes and passkeys.',
        'current_password' => 'Your password',
        'confirm_btn' => 'Reset',
        'own_account' => 'Use your own two-factor and passkeys pages to change your account.',
        'nothing_to_reset' => 'This admin has no two-factor authentication, nothing to reset.',
        'done' => 'Two-factor authentication of the admin was reset.',
        'notification' => [
            'subject' => 'Your two-factor authentication was reset',
            'line' => ':name reset your two-factor authentication on :time.',
            'enrol' => 'You can sign in with your password and set up two-factor authentication again.',
            'unexpected' => 'If you did not ask for this, contact :name or another administrator.',
        ],
    ],
];
