# Get Started

Create a PhalconKit application, run its first HTTP request, then connect a
database when you are ready to build a resource.

## Requirements

- PHP 8.5 or later, with the native Phalcon extension satisfying `^5.22.0`.
- Composer 2 and the PHP extensions required by the package.
- MySQL or MariaDB for database-backed features.
- Swoole only if you run the optional WebSocket server.

Check the PHP executable Composer will use:

```shell
php --version
php -r 'echo phpversion("phalcon") ?: "phalcon not loaded", PHP_EOL;'
composer --version
```

See [Runtime Requirements](phalcon-runtime-upgrades.md) if CLI and PHP-FPM use
different configurations.

## Create Your Application

```shell
composer create-project phalcon-kit/app my-api
cd my-api
cp .env.example .env
composer check-platform-reqs
```

On PowerShell, use `Copy-Item .env.example .env`. For an existing project that
does not use the skeleton, follow [Application Integration](application-integration.md).

Set your application's name and environment in `.env`:

```ini
APP_NAME="Project API"
APP_ENV=local
APP_DEBUG=false
APP_CACHE=false
```

Keep `.env` untracked. Put module definitions, providers, permissions, and model
aliases in `src/Config.php`; put deployment-specific values in the environment.

## Run An HTTP Request

From the application directory:

```shell
php -S 127.0.0.1:8080 -t public public/index.php
```

In another terminal:

```shell
curl --include http://127.0.0.1:8080/api
```

The skeleton's index action responds with HTTP 200 and this JSON shape; the
timestamp changes on each request:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "OK",
  "code": 200,
  "response": [],
  "view": []
}
```

This verifies the HTTP bootstrap without a database. The built-in PHP server is
for local development. In deployment, point your web server at `public/`; see
[Web Servers And WebSockets](web-server-and-websocket.md).

Check the CLI entrypoint separately:

```shell
./bin/phalcon-kit --help
```

On Windows, invoke PHP explicitly: `php bin/phalcon-kit --help`.

## Connect A Database

Create an empty development database and an application database account, then
set:

```ini
DATABASE_HOST=127.0.0.1
DATABASE_PORT=3306
DATABASE_DBNAME=my_api
DATABASE_USERNAME=my_api
DATABASE_PASSWORD="replace-with-your-local-password"
```

Core's default connection options target MySQL. For MariaDB, use the connection
options in [Configuration](configuration.md#mysql-and-mariadb) before running
migrations or scaffolding.

A project-only resource can use just its own tables. Authentication, sessions,
and other built-in features require their supporting tables. To install them
in a fresh database with an empty migration directory, follow
[Database Migrations](database-migrations.md#fresh-core-installation). Installing
Composer dependencies does not create tables or user accounts.

## Where To Put Your Code

| Path | Your application code |
| --- | --- |
| `src/Config.php` | Modules, services, permissions, model aliases |
| `src/Models/` | Concrete models and business rules |
| `src/Models/Abstracts/` | Generated schema accessors and relationships |
| `src/Modules/Api/Controllers/` | REST controllers and resource policies |
| `src/Modules/Cli/Tasks/` | Application commands |
| `src/Modules/Ws/Tasks/` | Optional WebSocket protocol |
| `resources/migrations/` | Versioned application schema |
| `tests/Unit/` | Application tests |
| `storage/` | Runtime files, cache, and logs |
| `public/` | The only web document root |

Keep your application's `composer.lock` in version control so development,
testing, and deployment install the same dependency versions.

## Build Something

Continue with [Your First REST Resource](first-rest-resource.md). It includes a
complete table, controller, permission configuration, and requests with their
results. Then add [Authentication](authentication.md) for protected actions.
