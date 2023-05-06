# Admin Auth

This package handles authentication into Admin UI interface for our [Craftable](https://github.com/BRACKETS-by-TRIAD/craftable) (`brackets/craftable`) package. It provides these features:
- User authentication
- Reset password
- Account activation

Provided functionality is ready to use - package exposes a set of routes, it has controllers and views (based on `brackets/admin-ui` admin template).

![Admin login form](https://docs.getcraftable.com/assets/login-form.png "Admin login form")

You can find full documentation at https://docs.getcraftable.com/#/admin-auth

## Run tests

To run tests use this docker environment.

```shell
  docker-compose run -it test vendor/bin/phpunit
```

To switch between postgresql and mariadb change in `docker-compose.yml` DB_CONNECTION environmental variable:

```git
- DB_CONNECTION: pgsql
+ DB_CONNECTION: mysql
```

## Issues
Where do I report issues?
If something is not working as expected, please open an issue in the main repository https://github.com/BRACKETS-by-TRIAD/craftable.
