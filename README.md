# Phalcon Kit Core

[![CI](https://github.com/phalcon-kit/core/actions/workflows/main.yml/badge.svg)](https://github.com/phalcon-kit/core/actions/workflows/main.yml)
![Version](https://img.shields.io/packagist/v/phalcon-kit/core)
![PHP](https://img.shields.io/packagist/dependency-v/phalcon-kit/core/php)
![Downloads](https://img.shields.io/packagist/dt/phalcon-kit/core)
[![Legacy Installs](https://img.shields.io/packagist/dt/zemit-cms/core?label=legacy%20installs)](https://packagist.org/packages/zemit-cms/core)
[![Docs](https://img.shields.io/badge/docs-guides-blue)](guides/README.md)
![License](https://img.shields.io/packagist/l/phalcon-kit/core)

Build Phalcon applications faster, especially REST APIs backed by a real
database schema.

Phalcon Kit Core sits on top of the [Phalcon PHP Framework](https://phalcon.io)
and gives you the application plumbing most teams write again and again:
bootstrap, config, providers, REST controllers, model scaffolding, eager
loading, identity, permissions, CLI tasks, WebSocket workers, logging, helpers,
and quality tooling.

You still write normal Phalcon/PHP. Phalcon Kit handles the repetitive structure
so you can spend more time on the resource rules, business logic, and product
behavior.

## Project Independence

Phalcon Kit is an independent open source project. It is not part of the
official Phalcon PHP Framework project and is not affiliated with, endorsed by,
or sponsored by the Phalcon project or its maintainers. References to Phalcon
describe compatibility with the upstream framework.

## Quick Start

Start a new application from the
[`phalcon-kit/app`](https://packagist.org/packages/phalcon-kit/app) skeleton:

```shell
composer create-project phalcon-kit/app my-api
```

Add the core package to an existing Phalcon application:

```shell
composer require phalcon-kit/core
```

New projects should use `phalcon-kit/core`. Older projects may still reference
[`zemit-cms/core`](https://packagist.org/packages/zemit-cms/core), the previous
package name. Keep old projects pinned until you are ready to test the package
name migration.

## Why Use It

Use Phalcon Kit when you want Phalcon's speed and low-level control, but you do
not want every project to re-invent the same API and model infrastructure.

It is useful when you need to:

- turn database tables into typed Phalcon models;
- expose model-backed REST resources quickly;
- save nested one-to-many or many-to-many payloads;
- filter, search, sort, paginate, and eager-load relations consistently;
- restrict rows by user, role, project, workspace, tenant, or ownership rules;
- use JWT/session identity, impersonation, and role-based access control;
- keep CLI, WebSocket, API, and web modules on the same bootstrap/config;
- let generated model code track the database while concrete models stay clean.

## From Database To API

The usual workflow is:

1. Create or update the database schema.
2. Run migrations.
3. Run the scaffolder.
4. Add business logic to concrete models.
5. Add a small REST controller that declares save/filter/search/eager-load
   policy.

Example resource controller:

```php
<?php

namespace App\Modules\Api\Controllers;

final class FooBarController extends AbstractController
{
    public function initializeSaveFields(): void
    {
        $this->setSaveFields([
            'label',
            'status',
            'usernode' => [
                'userId',
                'type',
                'deleted',
            ],
        ]);
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields([
            'id',
            'label',
            'status',
            'UserNode.userId',
            'UserNode.type',
            'deleted',
        ]);
    }

    public function initializeWith(): void
    {
        $this->setWith([
            'UserNode.UserEntity',
        ]);
    }
}
```

That controller participates in the standard REST flow: list/detail lookup,
save/create/update, delete/restore, filter/search/order/limit handling, relation
assignment, exposers or transformers, eager loading, and permission conditions.

See [Build Your First REST Resource](guides/first-rest-resource.md) for the
complete schema-to-controller example with request and response payloads.

## What You Write

| You write | Phalcon Kit handles |
| --- | --- |
| Database schema and migrations | Generated model abstracts, accessors, column maps, validations, relationships |
| Concrete model behavior | Shared model base, behaviors, soft delete, UUIDs, slug, blameable fields |
| REST resource policy | Request parsing, filtering, searching, saving, exposing, eager loading |
| Permission config and row rules | ACL expansion, role inheritance, controller/model/task checks |
| Transformers for complex output | Fractal manager helpers and relation-loaded includes |
| CLI/WebSocket task logic | Shared bootstrap, DI services, config, identity context |

## Application Shape

Most applications using this package follow this shape:

```text
app/
  Config/             app config, providers, permissions
  Models/             concrete business logic
  Models/Abstracts/   generated schema layer
  Modules/Api/        REST controllers
  Modules/Cli/        CLI tasks
  Modules/Ws/         WebSocket tasks
resources/
  migrations/         database migrations
public/
  index.php           web front controller
```

Generated files mirror the database. Your concrete models, controllers, config,
services, transformers, and tasks remain application-owned.

## Requirements

- PHP `>= 8.5`
- Phalcon `^5.14.2`
- Composer
- A PDO-compatible database supported by Phalcon
- MySQL 8+ for the core test/scaffold baseline

Recommended extensions for common applications: APCu, Redis, Swoole, Opcache,
IMAP, sockets, SimpleXML, and GD.

## Learn By Task

- Build your first resource: [Build Your First REST Resource](guides/first-rest-resource.md)
- Start a project: [Getting Started](guides/getting-started.md)
- Configure modules and providers: [Configuration](guides/configuration.md)
- Generate models from a database: [Database And Scaffolding](guides/database-scaffolding.md)
- Avoid N+1 queries: [Models And Eager Loading](guides/models-and-eager-loading.md)
- Build model-backed APIs: [REST APIs](guides/rest-api.md)
- Add roles and row-level access: [Identity And Permissions](guides/identity-and-permissions.md)
- Deploy behind PHP-FPM or WebSocket proxying: [Web Server And WebSocket](guides/web-server-and-websocket.md)
- Run checks before release: [Quality And Maintenance](guides/quality-and-maintenance.md)
- Upgrade the Phalcon extension: [Phalcon Runtime Upgrades](guides/phalcon-runtime-upgrades.md)
- Use the bundled AI skills: [AI-Assisted Development](AI.md)
- Migrate from the old package name: [Migration From zemit-cms/core](guides/migration-from-zemit.md)
- Migrate old RESTful resources: [Migrate RESTful 0.x Resources To 1.x](guides/migration-restful-0x-to-1x.md)

The full guide index is in [guides/README.md](guides/README.md).

## FAQ

### Is Phalcon Kit a replacement for Phalcon?

No. Phalcon Kit extends Phalcon. You still use Phalcon models, controllers,
services, DI, routing, validation, and events. Phalcon Kit adds application
structure, scaffolding, REST conventions, identity, permissions, and reusable
helpers around them.

### Is Phalcon Kit part of the official Phalcon project?

No. Phalcon Kit is independently maintained. It is not affiliated with,
endorsed by, or sponsored by the official Phalcon PHP Framework project.

### Do I need to know Phalcon?

Yes, at least the basics. Phalcon Kit is most useful when you want to build with
Phalcon but avoid repeatedly writing the same model/API/permission plumbing.

### Does it generate controllers?

The main supported scaffolding path is model generation from the database:
abstracts, interfaces, relationships, validations, enum classes, and tests.
REST controllers are usually small app-owned classes because save/filter/expose
policy is business-specific.

### Should I use exposers or transformers?

Use exposers for simple CRUD output. Use transformers when public clients need a
stable response shape, conditional includes, renamed fields, or better control
over nested resources.

### Can I use Nginx instead of Apache?

Yes. Phalcon Kit needs a web server or proxy that serves `public/` and forwards
PHP requests to PHP-FPM. Nginx, Apache, Caddy, containers, and platform proxies
are all valid.

### What happened to zemit-cms/core?

`zemit-cms/core` is the historical package name. New projects should install
`phalcon-kit/core`. Existing projects can stay pinned until they are ready to
test the package-name migration.

## For Contributors

```shell
composer qa
composer qa:static
composer qa:security
composer qa:test
composer qa:style
```

See [CONTRIBUTING.md](CONTRIBUTING.md), [SECURITY.md](SECURITY.md), and
[CHANGELOG.md](CHANGELOG.md) before opening a pull request.

## AI-Assisted Development

Phalcon Kit includes optional agent instructions under `resources/skills/`.
They help AI coding agents follow the same PhalconKit conventions documented in
the human-facing guides: database-first scaffolding, REST resources, eager
loading, transformers, identity, permissions, CLI tasks, WebSocket workers, and
provider/config patterns. They do not add runtime AI behavior or change PHP
APIs.

See [AI-Assisted Development](AI.md) for the bundled skills, example prompts,
safety defaults, and maintainer rules for keeping human docs and agent
references synchronized.

## Package History

Phalcon Kit Core continues the package formerly known as `zemit-cms/core`.
Existing projects can stay on pinned legacy constraints until migration is
planned. New installations should use `phalcon-kit/core`.

## License

Phalcon Kit Core is released under the [BSD 3-Clause License](LICENSE).

(c) 2017-present, Phalcon Kit Team. All rights reserved.
