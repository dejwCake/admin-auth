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
  password; disabling is refused while two-factor is required for the admin and TOTP is their only
  second factor (a passkey counts, see "Passkeys").
- **Impersonal login.** When an admin whose session passed two-factor uses impersonal login from
  the admin-users listing, the session stays verified for the impersonated admin: no challenge, no
  forced enrolment. A password login always starts unverified.

To require it, set:

| Env | Config (`admin-auth.two_factor.…`) | |
|---|---|---|
| `ADMIN_TWO_FACTOR_REQUIRED=true` | `required` | admins without a second factor (TOTP or a passkey) are held on `/admin/two-factor` until they enrol; the passkeys page stays reachable |
| `ADMIN_TWO_FACTOR_REQUIRED_ROLES=Administrator,Editor` | `required_roles` | limits `required` to admins with one of these roles; empty = every admin |

The name shown in the authenticator app is `admin-auth.two_factor.issuer`, `app.name` by default.

The feature needs the `two_factor_*` columns on `admin_users` (publish and run the migrations).
Without them admin pages keep working, every admin counts as not enrolled and the page's
"Authenticator app" section says which migration to run; with two-factor required, admins are held on that notice. The admin model needs
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

  It removes the TOTP data and every passkey of the admin, lists them and asks for confirmation
  unless `--force` is given, works even when two-factor is required (the admin is then asked to
  enrol again on their next page) and leaves open sessions logged in.

The pages mount the `TwoFactorOverview`, `TwoFactorSettings` and `TwoFactorChallengeForm`
components from `@dejwcake/craftable`, which `admin.js` has to register (the admin-ui install stub
does). The admin-auth install command adds the "Two-factor authentication" link to the profile
dropdown.

### Second factors

`/admin/two-factor` shows whether two-factor is on (an admin with any active second factor is
protected) and one section per second factor, each with its own status. The factors are listed in
`admin-auth.two_factor.factors`, in that order:

```php
'two_factor' => [
    // …
    'factors' => [
        Brackets\AdminAuth\TwoFactor\Factors\TotpFactor::class,     // "Authenticator app", managed in place
        Brackets\AdminAuth\TwoFactor\Factors\PasskeyFactor::class,  // "Passkeys", links to /admin/passkeys
    ],
],
```

Without the key (e.g. a published config with its own `two_factor` array) these two are used. A
further factor (e-mail or SMS codes, say) is a class implementing
`Brackets\AdminAuth\TwoFactor\Contracts\SecondFactor`, resolved through the container:

- `isAvailableFor()` and `isActiveFor()` say whether its migration has run and whether the admin
  has it;
- `present()` returns a `SecondFactorPresentation`: name, icon, status and either a link or a
  registered Vue component (with its props) that manages the factor in place and emits
  `update:active` when it turns on or off.

An unknown class in the list fails the page with an error naming it. The list drives the settings
page only: the login challenge, forced enrolment and the security reset know the authenticator app
and passkeys, so a new factor also needs its own challenge.

## Passkeys

Every admin can add passkeys (WebAuthn / FIDO2: Touch ID, Windows Hello, Android, iCloud / Google /
1Password keychains, security keys) on the "Passkeys" page at `/admin/passkeys` (route
`brackets/admin-auth::admin/passkeys`). Like TOTP, passkeys are always available; there is no switch.

- **The page** lists the admin's passkeys (name, added, last used) and adds, renames and removes
  them. Adding and removing ask for the current password; a new passkey's name is prefilled from
  the browser and the OS ("Chrome on macOS").
- **Passwordless login.** The login page shows "Sign in with a passkey", and browsers that support
  it offer passkeys in the e-mail field's autofill. The device checks the user (biometrics or PIN),
  so a passkey login counts as two factors and skips the challenge. It applies the same forbidden
  and activation checks as the password login; a soft-deleted admin cannot sign in. 5 failed
  passkey logins per minute and IP address lock it for the rest of the minute.
- **Second factor.** After a password login, an admin with a passkey can pass the two-factor
  challenge with it instead of a code. Failed attempts count toward the same limit as codes.
- **Forced two-factor** (`ADMIN_TWO_FACTOR_REQUIRED`) is satisfied by TOTP **or** a passkey. A
  forced admin can't remove their last second factor.
- **Reset.** `admin-auth:two-factor-reset` also removes the admin's passkeys.

Passkeys need a **secure context**: HTTPS, or `localhost` in development. On plain HTTP the browser
hides the passkey button and the page says passkeys are unavailable — with Harbor use
`http://localhost:<port>` or enable its TLS.

The relying party comes from `APP_URL` and `APP_NAME`. Set these when the admin is served on another
host:

| Env | Config (`admin-auth.passkeys.…`) | |
|---|---|---|
| `ADMIN_PASSKEYS_RP_ID=example.com` | `rp_id` | the domain passkeys are bound to: the admin's host or a registrable suffix of it; default the host of `app.url` |
| `ADMIN_PASSKEYS_RP_NAME="Example admin"` | `rp_name` | the name the browser shows; default `app.name` |
| `ADMIN_PASSKEYS_ALLOWED_ORIGINS=https://admin.example.com` | `allowed_origins` | comma-separated origins the admin is served on; default the scheme, host and port of `app.url` |

A rejected passkey response is logged as a warning with the origin the browser signed for, which
tells a wrong `ADMIN_PASSKEYS_ALLOWED_ORIGINS` from an attack.

- **Changing the RP id later makes every existing passkey unusable.** Admins can still sign in with
  their password (and TOTP), or be reset with the command, and add new passkeys.
- **Synced passkeys** (iCloud, Google, password managers) are only as safe as that account. They are
  still phishing-resistant; attestation is not checked (`none`), so any authenticator model works.

The feature needs the `admin_passkeys` table (publish and run the migrations). Without it admin
pages and the password login keep working, every admin counts as having no passkeys, the login page
has no passkey option and the page says which migration to run. The table's foreign key points at
`admin_users`; if your admin guard uses another table, edit the published migration. Force-deleting
an admin removes their passkeys; the generated CRUD soft-deletes, which keeps them (a restore brings
them back). The admin model needs `Brackets\AdminAuth\Passkeys\Contracts\PasskeyAuthenticatable` and
the `Brackets\AdminAuth\Passkeys\Traits\HasPasskeys` trait (`Brackets\AdminAuth\Models\AdminUser`
has both).

The page mounts the `PasskeySettings` component from `@dejwcake/craftable`, which `admin.js` has to
register (the admin-ui install stub does); the login and challenge forms get the passkey option from
props. The admin-auth install command adds the "Passkeys" link to the profile dropdown.

## Resetting another admin's two-factor

An admin who lost their phone and recovery codes, or their only passkey, can be helped by another
admin from the admin-users listing: the "Reset two-factor" row action removes the target's TOTP data
and all their passkeys, as `admin-auth:two-factor-reset` does on the server.

- **Endpoint:** `POST /admin/admin-users/{id}/security-reset` (route
  `brackets/admin-auth::admin/admin-users/security-reset`), under the admin group, so the acting
  admin must have passed their own two-factor challenge.
- **Permission:** `admin.admin-user.security-reset`, granted to the `Administrator` role by the
  publishable migration `add_admin_security_reset_permission` (and by craftable's default
  permissions on a fresh install).
- **Confirmation:** the acting admin's current password. Nobody can reset themselves; their own
  two-factor and passkeys pages cover that.
- **Trail:** the target gets an e-mail naming who reset it and when (only when something was
  removed), and an info log entry records both admins' ids and what was removed.

It does not log the target out of their open sessions, does not reset their password and keeps no
audit table. To lock someone out, forbid or delete them. The button is generated by
admin-generator into the admin-users listing when the project has second factors and this route
(`@dejwcake/craftable`'s `SecurityResetButton`).

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
| Two-factor authentication | `admin.two-factor.settings` | `account/TwoFactorOverview.vue`, `account/TwoFactorSettings.vue` |
| Two-factor challenge | `admin.two-factor.challenge` | `auth/TwoFactorChallengeForm.vue` |
| Passkeys | `admin.passkeys.index` | `account/PasskeySettings.vue` |

The passkey components share `composables/usePasskeys.js` (`@craftable/composables/usePasskeys.js`),
which a project can override alone, e.g. for a different autofill behaviour.

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
