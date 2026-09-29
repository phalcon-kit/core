# PhalconKit User Guide

Use PhalconKit as the application layer between Phalcon and your project.
These guides describe the current API and use the App skeleton's paths unless
an example explicitly says otherwise.

## Build Your First Application

1. [Get started](getting-started.md): install, configure, and run the skeleton.
2. [Build your first resource](first-rest-resource.md): create a table, generate
   its model, expose a controlled API, and verify requests and responses.
3. [Add authentication](authentication.md) and [permissions](identity-and-permissions.md).
4. [Run your application](web-server-and-websocket.md) behind a web server.

Already have a project? Start with [Application Integration](application-integration.md).

## REST API Handbook

| Guide | What you will find |
| --- | --- |
| [REST Controllers](rest-api.md) | Every built-in action, controller setup, field policies, and extension points |
| [Requests And Responses](rest-requests-and-responses.md) | URLs, HTTP methods, JSON/form/query inputs, response envelopes, errors, and client examples |
| [Filtering And Pagination](rest-filtering.md) | Filter objects, all operator families, nested AND/OR groups, search, sorting, limits, and offsets |
| [Writes And Batches](rest-writes.md) | Create/update/save intent, validation, partial batch success, delete, restore, and reorder |
| [Relationships](rest-relationships.md) | Eager loading, client-selected graphs, related filters, nested writes, and safe exposure |
| [Counts, Aggregates, And Exports](rest-aggregates.md) | List totals, grouped counts, distinct facets, calculations, and CSV downloads |
| [API Scenario Checklist](rest-scenarios.md) | Request/result cases to verify for your own resource |

Examples distinguish the complete HTTP envelope from a `view` fragment. Each
resource controls its exposed fields, enabled actions, and row access, so copy
the accompanying controller configuration as well as the request.

## Application Development

| Task | Guide |
| --- | --- |
| Decide where application code belongs | [Architecture](architecture.md) |
| Configure environments, modules, and services | [Configuration](configuration.md) |
| Connect Core to your own bootstrap | [Application Integration](application-integration.md) |
| Manage application tables | [Database Migrations](database-migrations.md) |
| Generate and regenerate models | [Database And Scaffolding](database-scaffolding.md) |
| Work with model relationships and behaviors | [Models And Eager Loading](models-and-eager-loading.md) |
| Choose the tables and services a feature needs | [Feature Setup](feature-contracts.md) |
| Sign in, refresh tokens, and sign out | [Authentication](authentication.md) |
| Grant actions and restrict rows | [Identity And Permissions](identity-and-permissions.md) |
| Configure keys, CORS, and sensitive fields | [Application Security](security-hardening.md) |
| Write a command | [CLI Tasks](cli-tasks.md) |
| Serve HTTP and WebSockets | [Web Servers And WebSockets](web-server-and-websocket.md) |
| Check PHP and Phalcon in each runtime | [Runtime Requirements](phalcon-runtime-upgrades.md) |
| Test your application's behavior | [Application Testing](application-testing.md) |
| Find a focused implementation example | [Application Cookbook](cookbook.md) |
| Resolve a failure | [Troubleshooting](troubleshooting.md) |

## Existing Applications

Use the [migration index](migrations/README.md) for package/namespace changes,
older REST resources, Core 4, and App layout/dependency changes. These guides
share a consistent preparation, change, verification, and rollback structure.

## Reference And Help

- [Class reference](https://phalcon-kit.github.io/docs/api/Home/): exact classes,
  methods, and signatures.
- [Phalcon documentation](https://docs.phalcon.io/latest/): the underlying PHP
  framework's APIs.
- [Discussions](https://github.com/orgs/phalcon-kit/discussions): application
  questions and examples.
- [Issue tracker](https://github.com/phalcon-kit/core/issues): reproducible bugs
  and documentation corrections.

For changes to PhalconKit itself, use the [contribution guide](../CONTRIBUTING.md).
Project maintenance and release procedures are separate from application setup.
