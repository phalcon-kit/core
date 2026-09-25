# Getting Started

Only Core 4.x is maintained. Core and the App skeleton start the 4.x line at
**4.0.0**, with an opt-in
[fresh database baseline](database-migrations.md#fresh-core-installation).
See the [support policy](../SUPPORT.md) and
[Core 4.0 upgrade checks](upgrading-4.0.md#application-upgrade-checks).

The sections below describe application setup and the first workflows to verify:

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

Create an application from the App 4.0 skeleton:

```shell
composer create-project phalcon-kit/app:^4.0 my-api
cd my-api
cp .env.example .env
composer qa
```

App 4.0.0 requires Core `^4.0` and commits the tested stable dependency lockfile.
App deliberately skips 3.x to match Core's major version. Older App 2.x releases
target unsupported Core 3.x.

For an existing application, prepare an isolated upgrade checkout and read the
[upgrade guide](upgrading-4.0.md), then require the stable line:

```shell
composer require phalcon-kit/core:^4.0
```

Review dependency and lockfile changes and test the application's own flows.
Use tagged releases and keep the lockfile; `dev-master` follows ongoing
development. All earlier Core versions and the old `zemit-cms/core` package
are unmaintained and unsupported.

The skeleton can run its basic routes and CLI without a database. Features such
as identity, audit, and templates require their tables; the validated Core 4.0
schema is available through the [fresh-install migration guide](database-migrations.md).
Existing applications keep their migration history and need their own upgrade review.

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
in `src/Config.php`.

## 3. Check The Project Shape

A normal app has a small bootstrap and clear ownership boundaries:

```text
src/
  Bootstrap.php
  Config.php
  Models/
  Modules/Api/
bin/
  phalcon-kit
scripts/
  migration-run.sh
resources/
  migrations/
public/
  index.php
bootstrap.php
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
composer qa
```

If the application uses the database:

```shell
./scripts/migration-list.sh
./scripts/migration-run.sh
```

For retained Core models, first follow [Fresh Core Installation](database-migrations.md#fresh-core-installation).
The App migration directory starts empty; the Core baseline is an explicit choice.
For application-owned migrations, use the [SQL-file pattern](database-migrations.md#application-owned-sql-migrations).

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

require_once dirname(__DIR__) . '/bootstrap.php';

echo (new Bootstrap(Bootstrap::MODE_MVC))->run();
```

CLI entrypoint:

```php
#!/usr/bin/env php
<?php

use App\Bootstrap;

require_once dirname(__DIR__) . '/bootstrap.php';

echo (new Bootstrap(Bootstrap::MODE_CLI))->run();
```

WebSocket entrypoint:

```php
#!/usr/bin/env php
<?php

use App\Bootstrap;

if (!extension_loaded('swoole')) {
    fwrite(STDERR, "The optional Swoole extension is required to run bin/websocket.\n");
    exit(1);
}

require_once dirname(__DIR__) . '/bootstrap.php';

echo (new Bootstrap(Bootstrap::MODE_WS))->run();
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
