# Admin Auth

Admin Auth is a Laravel package that handles authentication for the Craftable admin interface in your application. It supports:
- Authenticating admin users (login and logout)
- Handling admin password resets
- Activating new admin accounts

Provided functionality is ready to use – package exposes a set of routes, it has controllers and views (based on `dejwCake/admin-ui` admin template).

![Admin login form](https://docs.getcraftable.com/assets/login-form.png "Admin login form")

This package is part of [Craftable](https://github.com/dejwCake/craftable) (`dejwCake/craftable`), an administration starter kit for Laravel 12. Forked from [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`).

You can find full documentation at https://docs.getcraftable.com/#/admin-auth

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

### Run tests

Run tests with pcov:
```shell
docker compose run -it --rm test ./vendor/bin/phpunit -d pcov.enabled=1
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:
```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
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

Mess detector (phpmd):
```shell
docker compose run -it --rm php-qa phpmd ./src,./install-stubs,./lang,./resources,./routes,./tests ansi phpmd.xml --suffixes php --baseline-file phpmd.baseline.xml
```