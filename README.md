# Phalcon Kit Core

Build PHP applications and database-backed APIs with Phalcon. PhalconKit adds
REST controllers, model scaffolding, relationships, authentication, permissions,
and shared HTTP, CLI, and WebSocket infrastructure to your application.

[![CI](https://github.com/phalcon-kit/core/actions/workflows/main.yml/badge.svg)](https://github.com/phalcon-kit/core/actions/workflows/main.yml)
[![Version](https://img.shields.io/packagist/v/phalcon-kit/core)](https://packagist.org/packages/phalcon-kit/core)
![PHP](https://img.shields.io/packagist/dependency-v/phalcon-kit/core/php)
[![Documentation](https://img.shields.io/badge/docs-user%20guide-blue)](https://phalcon-kit.github.io/docs/)
![License](https://img.shields.io/packagist/l/phalcon-kit/core)

**[Get started](guides/getting-started.md)** ·
**[Build your first API](guides/first-rest-resource.md)** ·
**[REST reference](guides/rest-api.md)** ·
**[All guides](guides/README.md)**

## What You Can Build

- CRUD APIs with explicit rules for readable, writable, searchable, and sortable fields.
- Search screens with nested filters, pagination, counts, and related records.
- Applications with authenticated users, roles, and row-level access rules.
- Database-backed workflows with model validation, nested saves, and soft deletion.
- Background commands and optional WebSocket services sharing your application's configuration.

Your application owns its schema, business logic, controllers, and response
policies. You continue to use Phalcon's models, routing, dependency injection,
and events.

## Start An Application

Install PHP 8.5+, the Phalcon extension satisfying `^5.22.0`, and Composer 2, then run:

```shell
composer create-project phalcon-kit/app my-api
cd my-api
cp .env.example .env
php -S 127.0.0.1:8080 -t public public/index.php
```

In another terminal:

```shell
curl --include http://127.0.0.1:8080/api
```

The skeleton includes HTTP routes, a CLI entrypoint, application configuration,
and model-generation scripts. Its basic routes work before you configure a
database. Follow [Getting Started](guides/getting-started.md) to connect your
database and choose the features your application needs.

To add the library to your own project:

```shell
composer require phalcon-kit/core
```

Installing the library does not create an application bootstrap or database
schema. Use the [application integration guide](guides/application-integration.md)
to connect it to your project.

## From A Table To An API

1. Create your application's tables and migrations.
2. Generate models with `./scripts/generate-models.sh` in the App skeleton.
3. Add a resource controller with explicit field and access policies.
4. Call the endpoint from your frontend or another client.

For example, a configured project resource can return a page of matching rows
and the total number of matches:

```shell
curl --get 'http://127.0.0.1:8080/api/project/find' \
  --data-urlencode 'filters[0][field]=status' \
  --data-urlencode 'filters[0][operator]==' \
  --data-urlencode 'filters[0][value]=active' \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'limit=20' \
  --data-urlencode 'count=1'
```

An illustrative response, with the resource's exposed fields:

```json
{
  "timestamp": "2026-09-29T12:00:00+00:00",
  "status": "OK",
  "code": 200,
  "response": true,
  "view": {
    "data": [{"id": 1, "label": "Customer portal", "status": "active"}],
    "count": 1
  }
}
```

The [first resource tutorial](guides/first-rest-resource.md) supplies the schema,
controller, access policy, and commands needed to run this example. The
[REST reference](guides/rest-api.md) covers each built-in action and its results.

## Find The Guide For Your Task

| I want to… | Read |
| --- | --- |
| Install and run an application | [Getting Started](guides/getting-started.md) |
| Add PhalconKit to a project | [Application Integration](guides/application-integration.md) |
| Build a model-backed endpoint | [First REST Resource](guides/first-rest-resource.md) |
| Send requests and handle responses | [REST Requests And Responses](guides/rest-requests-and-responses.md) |
| Search, filter, sort, and paginate | [REST Filtering](guides/rest-filtering.md) |
| Create, update, or save batches | [REST Writes](guides/rest-writes.md) |
| Load and save related records | [REST Relationships](guides/rest-relationships.md) |
| Build counters, facets, and exports | [REST Counts And Aggregates](guides/rest-aggregates.md) |
| Sign in users and restrict access | [Authentication](guides/authentication.md) and [Permissions](guides/identity-and-permissions.md) |
| Configure services and modules | [Configuration](guides/configuration.md) |
| Create tables and generate models | [Migrations](guides/database-migrations.md) and [Scaffolding](guides/database-scaffolding.md) |
| Write commands or serve WebSockets | [CLI Tasks](guides/cli-tasks.md) and [Web Servers And WebSockets](guides/web-server-and-websocket.md) |
| Diagnose an unexpected result | [Troubleshooting](guides/troubleshooting.md) |

For an existing application changing package, layout, or API contracts, use
[the migration guides](guides/migrations/README.md).

Use the [class reference](https://phalcon-kit.github.io/docs/api/Home/) when you need
an exact method signature. The guides explain how to use those methods in an
application. Optional [AI-assisted development](AI.md) instructions cover the
same conventions.

## Project Information

PhalconKit is independently maintained and is not affiliated with or endorsed
by the official Phalcon project. See the [Phalcon documentation](https://docs.phalcon.io/latest/)
for native framework APIs.

[Report an issue](https://github.com/phalcon-kit/core/issues) ·
[Contributing](CONTRIBUTING.md) · [Security](SECURITY.md) ·
[Support](SUPPORT.md) · [Changelog](CHANGELOG.md)

Released under the [BSD 3-Clause License](LICENSE).
