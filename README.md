# Phalcon Kit Core

[![CI](https://github.com/phalcon-kit/core/actions/workflows/main.yml/badge.svg)](https://github.com/phalcon-kit/core/actions/workflows/main.yml)
![Version](https://img.shields.io/packagist/v/phalcon-kit/core)
![PHP](https://img.shields.io/packagist/dependency-v/phalcon-kit/core/php)
![Downloads](https://img.shields.io/packagist/dt/phalcon-kit/core)
[![Legacy Installs](https://img.shields.io/packagist/dt/zemit-cms/core?label=legacy%20installs)](https://packagist.org/packages/zemit-cms/core)
![License](https://img.shields.io/packagist/l/phalcon-kit/core)

Phalcon Kit Core is a modular application toolkit built on top of the
[Phalcon PHP Framework](https://phalcon.io). It provides the framework-level
pieces most applications need: bootstrap, configuration, service providers,
modules, REST controllers, model behaviors, generated model scaffolding,
permissions, identity, CLI tasks, WebSocket support, logging, helpers, and
agent-ready documentation.

The goal is simple: let the database schema and framework conventions handle
the repetitive structure, so application code can focus on business logic.

## When To Use It

Use Phalcon Kit when you want a Phalcon application with a repeatable structure
for APIs, models, permissions, CLI tasks, and long-running workers.

It is especially useful for database-first applications where the schema is the
source of truth and generated model layers should carry the repetitive column,
relationship, and validation details.

## Install

For a new application, start from the app skeleton:

```shell
composer create-project phalcon-kit/app my-app
```

For an existing Phalcon project:

```shell
composer require phalcon-kit/core
```

New projects should use `phalcon-kit/core`. The package was previously
distributed as [`zemit-cms/core`](https://packagist.org/packages/zemit-cms/core),
which remains available on Packagist for historical releases and existing
installations. If you arrived through the old package URL, use this repository
for issues, support, and migration context.

## Requirements

- PHP `>= 8.5`
- Phalcon `^5.13`
- Composer
- PDO-compatible database supported by Phalcon, with MySQL 8+ used by the core
  test and scaffold baseline
- Recommended extensions for common applications: APCu, Redis, Swoole, Opcache,
  IMAP, sockets, SimpleXML, and GD

## What It Provides

- **Config-first bootstrap**: compose modules, providers, model aliases,
  permissions, router defaults, locale, and integrations through configuration.
- **Service providers**: register core services for config, request/response,
  database, cache, session, identity, logging, mail, OAuth, OpenAI, Redis,
  Swoole, and more.
- **Identity and permissions**: JWT/session identity, impersonation,
  role inheritance, ACL checks, and config-defined permission policies for
  controllers, actions, models, CLI tasks, and WebSocket tasks.
- **REST controller conventions**: model-backed APIs with save fields, filter
  fields, search fields, joins, dynamic joins, exposers, transformers,
  eager-loading, and row-level permission conditions.
- **Database-first scaffolding**: generate abstract models, interfaces,
  comments, typed accessors, column maps, relationships, validations, enum
  classes, and model tests from the real database schema.
- **Relationship-aware models**: work with single-to-many and many-to-many
  payloads, nested validation messages, soft-delete-aware relation updates, and
  generated aliases.
- **Batch eager loading**: use `findWith()`, `findFirstWith()`, controller
  `initializeWith()`, and relation-level query builders instead of lazy-loading
  loops.
- **Tooling and quality gates**: PHPUnit, Psalm, Psalm taint analysis, PHPStan,
  PHPCS, Composer audit, package skeleton checks, and generated API docs.
- **Agent-ready docs**: reusable Codex/agent skills that describe PhalconKit
  app and core-maintainer conventions.

## Application Shape

Most applications using this package follow this ownership model:

```text
app/
  Config/             application config, providers, permissions
  Models/             concrete business logic
  Models/Abstracts/   generated schema layer
  Modules/Api/        model-backed REST controllers
  Modules/Cli/        CLI tasks
  Modules/Ws/         WebSocket tasks
resources/
  migrations/         database migrations
public/
  index.php           web front controller
```

Generated files mirror the database. Concrete models, controllers, config
classes, and module code remain application-owned.

## Minimal Bootstrap

```php
<?php

use Phalcon\Autoload\Loader;

const APP_NAMESPACE = 'App';
const ROOT_PATH = __DIR__ . '/';
const VENDOR_PATH = ROOT_PATH . 'vendor/';
const APP_PATH = ROOT_PATH . 'app/';

$loader = new Loader();
$loader->setFiles([VENDOR_PATH . 'autoload.php']);
$loader->setNamespaces([APP_NAMESPACE => APP_PATH]);
$loader->setFileCheckingCallback(null);
$loader->register();

echo (new App\Bootstrap())->run();
```

```php
<?php

namespace App;

use App\Config\Config;

final class Bootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new Config());
    }
}
```

For a full setup, use the app skeleton or read
[Getting Started](guides/getting-started.md).

## Documentation

- [Architecture](guides/architecture.md)
- [Guide Index](guides/README.md)
- [Getting Started](guides/getting-started.md)
- [Configuration](guides/configuration.md)
- [Database And Scaffolding](guides/database-scaffolding.md)
- [Models And Eager Loading](guides/models-and-eager-loading.md)
- [REST APIs](guides/rest-api.md)
- [Identity And Permissions](guides/identity-and-permissions.md)
- [Resource Walkthrough](guides/resource-walkthrough.md)
- [Web Server And WebSocket](guides/web-server-and-websocket.md)
- [Quality And Maintenance](guides/quality-and-maintenance.md)
- [Release Process](guides/release.md)
- [AI-Assisted Development](AI.md)
- [Changelog](CHANGELOG.md)
- [Security Policy](SECURITY.md)
- [Support](SUPPORT.md)
- [Contributing](CONTRIBUTING.md)

Generated API documentation is produced with:

```shell
composer docs
```

The generated output is local build output and is not the primary public
documentation source.

## Quality Gates

```shell
composer qa
composer qa:static
composer qa:security
composer qa:test
composer qa:style
```

`composer qa` is the full maintainer gate. Run focused commands while
developing, then run the full gate before opening a pull request.

## AI-Assisted Development

Phalcon Kit ships optional agent instructions under `resources/skills/`.

- `resources/skills/phalconkit-app-developer` helps agents work inside
  applications using PhalconKit.
- `resources/skills/phalconkit-core-maintainer` helps agents modify this core
  package safely.

These assets are documentation and workflow guidance only. They do not add a
runtime AI layer or change PHP APIs.

For human-facing docs, start with `guides/`. For agent behavior and deeper
implementation conventions, use `resources/skills/`.

## Package History

Phalcon Kit Core is the continuation of the package formerly known as
`zemit-cms/core`. The old Packagist package keeps the historical install count
and legacy versions for existing users, while new installations should use
`phalcon-kit/core`. Keep old projects on their pinned constraints until you are
ready to upgrade, then migrate the package name during the normal dependency
update cycle.

## License

Phalcon Kit Core is released under the [BSD 3-Clause License](LICENSE).

(c) 2017-present, Phalcon Kit Team. All rights reserved.
