# Getting Started

This guide gets you from install to a runnable Phalcon Kit application. If your
main goal is a REST API, read this first and then continue with the
[Build Your First REST Resource](first-rest-resource.md).

By the end, you will have:

- dependencies installed against the current package requirements;
- environment-backed application configuration;
- working HTTP, CLI, and WebSocket entrypoints;
- a local HTTP process you can inspect with `curl`;
- the next commands for migrations, scaffolding, and tests.

!!! info "Before you begin"

    Install Composer and a PHP runtime satisfying the requirements published by
    `phalcon-kit/core`. The native Phalcon extension must be loaded by the same
    CLI binary Composer uses. A database is optional until you run migrations or
    use model-backed resources.

Verify the platform before creating the project:

```shell
php --version
php -r 'echo phpversion("phalcon") ?: "phalcon not loaded", PHP_EOL;'
composer --version
```

## 1. Create Or Install

For a new application, start from the
[`phalcon-kit/app`](https://packagist.org/packages/phalcon-kit/app) skeleton:

```shell
composer create-project phalcon-kit/app my-api
cd my-api
```

For an existing Phalcon application:

```shell
composer require phalcon-kit/core
```

Use `phalcon-kit/core` for new projects. The old `zemit-cms/core` package name
exists only for historical projects and pinned legacy installs.

## 2. Configure The Environment

Create or update `.env`:

```ini
APP_NAME="My API"

DATABASE_HOST=127.0.0.1
DATABASE_DBNAME=my_api
DATABASE_USERNAME=my_api
DATABASE_PASSWORD=secret
```

The app config reads environment values and registers modules, providers,
aliases, permissions, and integrations. Keep secrets in `.env`; keep structure
in `app/Config`.

## 3. Check The Project Shape

A normal app has a small bootstrap and clear ownership boundaries:

```text
app/
  Bootstrap.php
  Config/
  Models/
  Modules/Api/
resources/
  migrations/
public/
  index.php
loader.php
index.php
cli
websocket
```

Point the web server at `public/`, not the project root.

## 4. Run Locally

For a quick local test:

```shell
php -S 127.0.0.1:8000 -t public public/index.php
```

In another terminal, inspect the response:

```shell
curl --include http://127.0.0.1:8000/
```

A configured application response—or even an application-owned 404—proves the
request reached bootstrap and dispatch. A PHP source download, web-server 404,
or connection refusal means the request did not reach the application.

For production-like development, use PHP-FPM behind Nginx, Apache, Caddy, or a
container proxy. See [Web Server And WebSocket](web-server-and-websocket.md).

## 5. Verify Tooling

After installing dependencies:

```shell
composer validate --strict --no-check-publish
composer phpunit
```

If the application uses the database:

```shell
./bin/migration-list.sh
./bin/migration-run.sh
```

## 6. Build The First API Resource

The fastest path is:

1. Create the table.
2. Run migrations.
3. Run the scaffolder.
4. Add a model-backed API controller.
5. Configure permissions.

The full example is in [Build Your First REST Resource](first-rest-resource.md).

## Useful Entrypoints

Web entrypoint:

```php
<?php

use App\Bootstrap;

require 'loader.php';

echo (new Bootstrap())->run();
```

CLI entrypoint:

```php
#!/usr/bin/env php
<?php

use App\Bootstrap;

require 'loader.php';

echo (new Bootstrap('cli'))->run();
```

WebSocket entrypoint:

```php
#!/usr/bin/env php
<?php

use App\Bootstrap;

require 'loader.php';

echo (new Bootstrap('ws'))->run();
```

## Next Steps

- [Build Your First REST Resource](first-rest-resource.md): build a complete
  resource.
- [Configuration](configuration.md): configure modules, providers, aliases, and
  permissions.
- [Database And Scaffolding](database-scaffolding.md): generate model layers.
- [REST APIs](rest-api.md): configure resource controllers.
- [Developer Cookbook](cookbook.md): copy focused application recipes.
- [Troubleshooting](troubleshooting.md): diagnose boot, DI, routing, database,
  and runtime problems.

## Common Setup Problems

| Symptom | Check first |
| --- | --- |
| Composer says `ext-phalcon` is missing | Run `php --ri phalcon` with the CLI binary Composer uses |
| Browser shows PHP source or downloads a file | Configure PHP handling and point the server to `public/` |
| CLI finds classes that HTTP cannot | Compare FPM and CLI release paths, autoloaders, and environment |
| Database commands use the wrong schema | Check the CLI working directory and loaded `.env` values |
| A service is unavailable | Confirm its provider is registered in app config |

For a systematic diagnostic flow, use [Troubleshooting](troubleshooting.md).
