# Upgrade Guide: v1 to v2

## Requirements

| Dependency | v1 | v2 |
|---|---|---|
| PHP | ^8.2 | ^8.5 |
| Laravel | ^12.0 | ^13.0 |
| dejwcake/admin-ui | ^1.0 | ^2.0 |
| dejwcake/craftable-media | ^1.0 | ^2.0 |
| spatie/laravel-permission | ^6.15 | ^7.0 |
| phpunit/phpunit | ^11.5 | ^13.0 |
| orchestra/testbench | ^10.0 | ^11.0 |

Update your `composer.json`:

```json
"dejwcake/admin-auth": "^2.0"
```

## Breaking Changes

### 1. `install-stubs/` Removed — Config, Migrations, and Lang Moved

The `install-stubs/` directory has been removed entirely. Config, migrations, and language files are now located directly inside the package:

| v1 path | v2 path |
|---|---|
| `install-stubs/config/admin-auth.php` | `config/admin-auth.php` |
| `install-stubs/config/activation.php` | `config/activation.php` |
| `install-stubs/database/migrations/*` | `database/migrations/*` |
| `install-stubs/lang/admin.php` | Removed (see Language Files below) |
| `install-stubs/lang/activations.php` | Removed (see Language Files below) |

**Action required:** If you have published config or migrations from v1, no changes needed — your published copies remain. The package now uses `mergeConfigFrom` pointing to its own `config/` directory.

### 2. `last_login_at` Migration Merged

The separate `add_last_login_at_timestamp_to_admin_users_table` migration has been removed. The `last_login_at` column is now included in the `create_admin_users_table` migration.

**Action required:** If you already have both migrations from v1, no action needed. For fresh installs, only the single `create_admin_users_table` migration is published.

### 3. Language Files Restructured

The `install-stubs/lang/admin.php` and `install-stubs/lang/activations.php` files have been removed. Translations are now shipped as package translations under the `brackets/admin-auth` namespace in `lang/en/` and `lang/sk/`:

| v1 | v2 |
|---|---|
| `install-stubs/lang/admin.php` (all keys) | `lang/{locale}/admin.php` (UI labels only) |
| `install-stubs/lang/activations.php` | `lang/{locale}/activations.php` |
| Keys in `admin.php` under `passwords.*` | `lang/{locale}/resets.php` |

The translations are loaded via `loadTranslationsFrom` with the `brackets/admin-auth` namespace. Published translations now go to `lang/vendor/brackets/admin-auth/` instead of `lang/vendor/admin-auth/`.

**Action required:** If you have published translation overrides, move them:
```
# v1
lang/vendor/admin-auth/en/admin.php

# v2
lang/vendor/brackets/admin-auth/en/admin.php
lang/vendor/brackets/admin-auth/en/activations.php
lang/vendor/brackets/admin-auth/en/resets.php
```

### 4. Activation Facade Removed

`Brackets\AdminAuth\Activation\Facades\Activation` has been removed. Use dependency injection of `ActivationBrokerFactory` instead.

**Action required:** Replace any usage of the `Activation` facade:
```php
// v1
use Brackets\AdminAuth\Activation\Facades\Activation;
Activation::broker('admin_users')->sendActivationLink($credentials);

// v2
use Brackets\AdminAuth\Activation\Contracts\ActivationBrokerFactory;
// Inject via constructor
$this->activationBrokerFactory->broker('admin_users')->sendActivationLink($credentials);
```

### 5. `ActivationNotification` Moved

`Brackets\AdminAuth\Notifications\ActivationNotification` has been removed. The activation notification now lives at `Brackets\AdminAuth\Activation\Notifications\ActivationNotification`.

**Action required:** Update any imports:
```php
// v1
use Brackets\AdminAuth\Notifications\ActivationNotification;

// v2
use Brackets\AdminAuth\Activation\Notifications\ActivationNotification;
```

### 6. Blade Templates Rewritten — Vue Components Required

All Blade auth templates have been rewritten to use Vue 3 components from `@dejwcake/craftable` (provided by the `craftable-frontend` package). The templates are now thin wrappers that pass props to Vue components.

**v1** templates used inline `<auth-form>` with vee-validate directives directly in Blade.

**v2** templates use dedicated Vue components: `<login-form>`, `<forgot-password-form>`, `<reset-password-form>`, `<activation-form>`, `<activation-error>`.

Example — `login.blade.php`:
```blade
{{-- v1: Extended admin-ui master layout, inline Vue form --}}
@extends('brackets/admin-ui::admin.layout.master')
@section('content')
    <auth-form :action="..." inline-template>
        <form>...</form>
    </auth-form>
@endsection

{{-- v2: Extends new auth layout, uses Vue component with props --}}
@extends('brackets/admin-auth::admin.auth.layout.auth')
@section('auth-content')
    <login-form
        :action="'{{ $action }}'"
        :redirect-url="'{{ $redirectUrl }}'"
        :password-reset-url="'{{ $passwordResetUrl }}'"
        :translations="{{ json_encode([...]) }}"
        :status-message="'{{ session('status', '') }}'"
        :server-errors="{{ json_encode($errors->all()) }}"
    ></login-form>
@endsection
```

**New auth layout:** A new `resources/views/admin/auth/layout/auth.blade.php` has been added as the base layout for all auth pages.

**Removed:** `resources/views/admin/auth/includes/messages.blade.php` — error/status messages are now handled by Vue components via props.

**Action required:** If you have published/customized auth views:
1. Update your views to use the new Vue component syntax
2. Change `@extends` from `brackets/admin-ui::admin.layout.master` to `brackets/admin-auth::admin.auth.layout.auth`
3. Change `@section('content')` to `@section('auth-content')`
4. Remove any `@include('brackets/admin-auth::admin.auth.includes.messages')` calls

**New email templates:** Notification email templates have been added:
- `resources/views/admin/auth/emails/activation.blade.php`
- `resources/views/admin/auth/emails/reset-password.blade.php`

### 7. Controllers Now Pass Data to Views

All auth controllers now pass explicit data to views instead of relying on global helpers or implicit data. If you extend any controllers, update your `show*` methods:

- `LoginController::showLoginForm()` — passes `action`, `redirectUrl`, `passwordResetUrl`
- `ForgotPasswordController::showLinkRequestForm()` — passes `action`, `loginUrl`, `statusMessage`
- `ResetPasswordController::showResetForm()` — passes `token`, `email`, `action`, `redirectUrl`, `loginUrl`, `statusMessage`
- `ActivationEmailController::showLinkRequestForm()` — passes `action`, `statusMessage`

### 8. JSON Response Support Added

Controllers now return `JsonResponse` for AJAX/API requests (`$request->wantsJson()`):
- `ForgotPasswordController::sendResetLinkEmail()`
- `ResetPasswordController::reset()`
- `ActivationEmailController::sendActivationEmail()`

This is a non-breaking addition but changes the return type signatures if you override these methods.

### 9. Facades Replaced with Dependency Injection

All usage of Laravel facades has been replaced with constructor-injected contracts:

| v1 (Facade) | v2 (Injected contract) |
|---|---|
| `Auth::guard()` | `$this->authFactory->guard()` / `app('auth')->guard()` |
| `Password::broker()` | `$this->passwordBrokerFactory->broker()` / `app('auth.password')->broker()` |
| `Hash::make()` | `$this->hasher->make()` / `app('hash')->make()` |
| `Schema::hasColumn()` | `app('db.schema')->hasColumn()` |
| `Lang::get()` | `trans()` |
| `config()` helper | `$this->config->get()` (injected `Config` contract) |

**Action required:** If you extend any package classes that use these methods, update to use the injected dependencies or the `app()` helper equivalents.

### 10. `ThrottlesLogins::throwLockoutResponse()` Return Type Changed

```php
// v1
protected function throwLockoutResponse(Request $request): void

// v2
protected function throwLockoutResponse(Request $request): never
```

**Action required:** If you override this method, update the return type.

### 11. `AuthenticatesUsers::throwFailedLogin()` Removed

The `throwFailedLogin()` method has been removed from the `AuthenticatesUsers` trait. The validation exception is now thrown inline.

**Action required:** If you override `throwFailedLogin()`, move your logic into an override of the `attemptLogin` flow or handle it in a custom `sendFailedLoginResponse`.

### 12. Error Response Behavior Changed

Failed password reset and activation responses no longer expose specific error details. Instead:
- Failed responses are logged via `LoggerInterface`
- Users see generic success messages (preventing user enumeration)
- `ForgotPasswordController` returns a success response even on failure (security best practice)

### 13. Activation Route Name Changed

```php
// v1
'brackets/admin-auth::admin/activation'

// v2
'brackets/admin-auth::admin/activation/activate'
```

**Action required:** Update any references to this route name.

### 14. `bcrypt()` Helper Replaced

```php
// v1
'password' => bcrypt($password)

// v2
'password' => $this->hasher->make($password)
```

### 15. `PasswordReset` Event Dispatched

`ResetPasswordController` now dispatches `Illuminate\Auth\Events\PasswordReset` after a successful password reset. If you have event listeners for this event, they will now fire for admin password resets as well.

### 16. `array_merge()` Replaced with Spread Operator

Internal change, but if you override `credentials()` methods in controllers and call `parent::credentials()`, the behavior is identical.

## Migration Steps Summary

1. Update `composer.json` requirements (PHP ^8.5, spatie/laravel-permission ^7.0, etc.)
2. Run `composer update`
3. Move published translations from `lang/vendor/admin-auth/` to `lang/vendor/brackets/admin-auth/` and split into `admin.php`, `activations.php`, `resets.php`
4. Replace any `Activation` facade usage with dependency injection
5. Update `ActivationNotification` import path
6. Update published auth Blade templates to use Vue components (or re-publish with `php artisan vendor:publish --tag=views --provider="Brackets\AdminAuth\AdminAuthServiceProvider" --force`)
7. Update any route references from `brackets/admin-auth::admin/activation` to `brackets/admin-auth::admin/activation/activate`
8. Update any class extensions that override removed/changed methods
9. Ensure `@dejwcake/craftable` frontend package is installed and Vue auth components are registered
