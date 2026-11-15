# Admin Auth

Admin Auth is a Laravel package that handles authentication for the Craftable admin interface in your application. It supports:
- Authenticating admin users (login and logout)
- Handling admin password resets
- Activating new admin accounts
- The logged-in admin's own profile and password pages

Provided functionality is ready to use – package exposes a set of routes, it has controllers and views (based on `dejwCake/admin-ui` admin template).

![Admin login form](https://docs.getcraftable.com/assets/login-form.png "Admin login form")

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel 13. Forked from [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`).

You can find full documentation at https://docs.getcraftable.com/#/admin-auth

## Profile and password pages

The package serves the logged-in admin's "My profile" page at `/admin/profile` (first and last name,
e-mail, language, avatar) and the "Change password" page at `/admin/password`. Route names are
`admin/edit-profile`, `admin/update-profile`, `admin/edit-password` and `admin/update-password` —
the same URLs and names `admin:generate:admin-user:profile` generates.

- Changing the e-mail asks for the current password.
- Changing the password asks for the current password, applies the policy (at least 8 characters,
  upper- and lower-case letters, a number and a symbol, not found in known data breaches) and logs
  out the admin's other sessions. For this the package adds Laravel's `AuthenticateSession`
  middleware to the `admin` middleware group, so *any* password change — also a reset through the
  forgot-password e-mail — ends the admin's other sessions.
- The language options are craftable-translatable's `translatable.locales`; without
  craftable-translatable installed, only the application locale is offered.
- The avatar uploader is shown when the admin model has craftable-media's `avatar` collection
  (`Brackets\AdminAuth\Models\AdminUser` has).

Both pages are switched together by `admin-auth.profile.enabled` (env `ADMIN_AUTH_PROFILE`):

| Value | Package pages registered |
|---|---|
| `auto` (default) | only when the application has no route named `admin/edit-profile`, so a generated profile keeps working |
| `true` | always |
| `false` | never |

Nothing is registered when `admin-auth.use_routes` is `false`. The pages mount the `ProfileForm`
and `PasswordChangeForm` components from `@dejwcake/craftable`, which `admin.js` has to register
(the admin-ui install stub does).

## Two-factor authentication

Every admin can protect their account with a time-based one-time password (TOTP, RFC 6238 — Google
Authenticator, Authy, 1Password, …) on the "Two-factor authentication" page at `/admin/two-factor`
(route `brackets/admin-auth::admin/two-factor`). It is always available whenever the package routes
are on; there is no switch to turn the feature off, and by default it is optional.

- **Enrolment.** The admin scans a QR code (or types the key), confirms with a code and gets 8
  single-use recovery codes, shown once (copy / download). Starting again replaces an unconfirmed
  key. The secret is stored encrypted, recovery codes only as hashes.
- **Login.** After the password login (also one restored from the "remember me" cookie), an
  enrolled admin is sent to `/admin/two-factor/challenge` from every admin page until they enter a
  code or a recovery code; then they continue to the page they asked for. A code is accepted once,
  a recovery code is used up, and 5 failed attempts per minute lock the challenge for the rest of
  the minute. Admin AJAX calls made before that get a 403 with the challenge URL, which
  `@dejwcake/craftable`'s `useAdmin` follows.
- **Changes.** Regenerating the recovery codes and disabling two-factor ask for the current
  password; disabling is refused while two-factor is required for the admin.
- **Impersonal login.** When an admin whose session passed two-factor uses impersonal login from
  the admin-users listing, the session stays verified for the impersonated admin: no challenge, no
  forced enrolment. A password login always starts unverified.

To require it, set:

| Env | Config (`admin-auth.two_factor.…`) | |
|---|---|---|
| `ADMIN_TWO_FACTOR_REQUIRED=true` | `required` | admins without two-factor are held on `/admin/two-factor` until they enrol |
| `ADMIN_TWO_FACTOR_REQUIRED_ROLES=Administrator,Editor` | `required_roles` | limits `required` to admins with one of these roles; empty = every admin |

The name shown in the authenticator app is `admin-auth.two_factor.issuer`, `app.name` by default.

The feature needs the `two_factor_*` columns on `admin_users` (publish and run the migrations).
Without them admin pages keep working, every admin counts as not enrolled and the page says which
migration to run; with two-factor required, admins are held on that notice. The admin model needs
`Brackets\AdminAuth\TwoFactor\Contracts\TwoFactorAuthenticatable` and its trait
(`Brackets\AdminAuth\Models\AdminUser` has both).

Operations:

- **APP_KEY.** Secrets are encrypted with it. Rotate it with `APP_PREVIOUS_KEYS`, or every admin
  loses two-factor: a secret that cannot be decrypted counts as "no two-factor" (logged as an error).
- **Clock.** Codes are accepted 30 seconds either side; phones and servers must keep their time
  synchronised.
- **Cache.** Used codes are remembered in the cache for 150 seconds. Several servers must share the
  cache (as they already do for sessions and login throttling), or a code could be replayed on
  another one.
- **Lost device and recovery codes.** Turn two-factor off for that admin on the server:

  ```shell
  php artisan admin-auth:two-factor-reset admin@example.com
  ```

  It asks for confirmation unless `--force` is given, works even when two-factor is required (the
  admin is then asked to enrol again on their next page) and leaves open sessions logged in.

The pages mount the `TwoFactorSettings` and `TwoFactorChallengeForm` components from
`@dejwcake/craftable`, which `admin.js` has to register (the admin-ui install stub does). The
admin-auth install command adds the "Two-factor authentication" link to the profile dropdown.

## Customising pages

Every page of this package can be changed in the project without forking it.

**Views.** Publish them with

```shell
php artisan vendor:publish --provider="Brackets\AdminAuth\AdminAuthServiceProvider" --tag=views
```

They land in `resources/views/vendor/brackets/admin-auth/` with the package's directory layout,
and a file there is used instead of the package one. Existing files are not overwritten unless you
pass `--force`. Delete the files you don't change, or copy just the one page you need.

**Vue components.** `admin.js` imports the components by path, e.g.
`import ProfileForm from '@craftable/account/ProfileForm.vue'`. The `craftableOverrides()` plugin in
`vite.config.js` resolves `@craftable/<path>` to `resources/js/admin/<path>` when that file exists,
otherwise to `@dejwcake/craftable`'s `src/<path>`. To change a component, copy it to the same path
under `resources/js/admin/` (or run `npx craftable-publish-components account`) and edit the copy;
it builds unchanged, because package components import shared code only from `@dejwcake/craftable`
or `@craftable/…`. Projects without `craftableOverrides()` register their own component under the
same tag name in `admin.js` instead.

An overridden view or component no longer receives fixes from package updates — compare it with
the package version when you upgrade.

| Page | View (`brackets/admin-auth::…`) | Component (`@craftable/…`) |
|---|---|---|
| Login | `admin.auth.login` | `auth/LoginForm.vue` |
| Forgot password | `admin.auth.passwords.email` | `auth/ForgotPasswordForm.vue` |
| Reset password | `admin.auth.passwords.reset` | `auth/ResetPasswordForm.vue` |
| Activation | `admin.auth.activation.email`, `admin.auth.activation.error` | `auth/ActivationForm.vue`, `auth/ActivationError.vue` |
| My profile | `admin.profile.edit-profile` | `account/ProfileForm.vue` |
| Change password | `admin.profile.edit-password` | `account/PasswordChangeForm.vue` |
| Two-factor authentication | `admin.two-factor.settings` | `account/TwoFactorSettings.vue` |
| Two-factor challenge | `admin.two-factor.challenge` | `auth/TwoFactorChallengeForm.vue` |

## Issues
Where do I report issues?
If something is not working as expected, please open an issue in the main repository https://github.com/dejwCake/craftable.

## How to develop this project

### Composer

Update dependencies:
```shell
docker compose run -it --rm test composer update
```

Composer normalization:
```shell
docker compose run -it --rm php-qa composer normalize
```

### Run code analysis tools (php-qa)

PHP compatibility:
```shell
docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache
```

Code style:
```shell
docker compose run -it --rm php-qa phpcs -s --colors --extensions=php
```

Fix style issues:
```shell
docker compose run -it --rm php-qa phpcbf -s --colors --extensions=php
```

Static analysis (phpstan):
```shell
docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon
```

Regenerate the phpstan baseline:
```shell
docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon --generate-baseline=phpstan-baseline.neon
```

Mess detector (phpmd):
```shell
docker compose run -it --rm php-qa phpmd analyze
```

Regenerate the phpmd baseline:
```shell
docker compose run -it --rm php-qa phpmd analyze --generate-baseline
```

### Run tests

Run tests against mariadb:
```shell
docker compose run -it --rm -e DB_CONNECTION=mariadb test ./vendor/bin/phpunit
```

Run tests against postgresql:
```shell
docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```

Run tests with coverage:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit --coverage-text
```

### Run the whole PHP suite

Run every PHP check and the test suite against both databases in sequence (stops at the first failure):
```shell
docker compose run -it --rm test composer update \
  && docker compose run -it --rm php-qa composer normalize \
  && docker compose run -it --rm php-qa phpcs --standard=.phpcs.compatibility.xml --cache=.phpcs.cache \
  && docker compose run -it --rm php-qa phpcs -s --colors --extensions=php \
  && docker compose run -it --rm php-qa phpstan analyse --configuration=phpstan.neon \
  && docker compose run -it --rm php-qa phpmd analyze \
  && docker compose run -it --rm -e DB_CONNECTION=mariadb test ./vendor/bin/phpunit \
  && docker compose run -it --rm -e DB_CONNECTION=pgsql test ./vendor/bin/phpunit
```
