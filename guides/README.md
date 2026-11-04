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

## How PhalconKit Works

| Guide | What you will find |
| --- | --- |
| [Application Structure](architecture.md) | The parts of an application, who owns each directory, and where new code belongs |
| [How A Request Runs](request-lifecycle.md) | Bootstrap, providers, modules, dispatch listeners, and where to extend each step |
| [Configuration](configuration.md) | Environment, app config, modules, provider overrides, listeners, and permissions |
| [Feature Setup](feature-contracts.md) | The tables, models, and services each built-in feature needs |

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
| [Grow A Resource](resource-walkthrough.md) | Which layer to change as a resource gains fields, children, and rules |

Examples distinguish the complete HTTP envelope from a `view` fragment. Each
resource controls its exposed fields, enabled actions, and row access, so copy
the accompanying controller configuration as well as the request.

## Build Your Application

| Task | Guide |
| --- | --- |
| Manage application tables | [Database Migrations](database-migrations.md) |
| Generate and regenerate models | [Database And Scaffolding](database-scaffolding.md) |
| Work with model relationships and behaviors | [Models And Eager Loading](models-and-eager-loading.md) |
| Sign in, refresh tokens, and sign out | [Authentication](authentication.md) |
| Grant actions and restrict rows | [Identity And Permissions](identity-and-permissions.md) |
| Configure keys, CORS, and sensitive fields | [Application Security](security-hardening.md) |
| Write a command | [CLI Tasks](cli-tasks.md) |
| Call OpenAI with injectable transports and usage capture | [OpenAI](openai.md) |
| Find a focused implementation example | [Application Cookbook](cookbook.md) |

## Run And Operate

| Task | Guide |
| --- | --- |
| Serve HTTP and WebSockets, and run long-lived workers | [Web Servers And WebSockets](web-server-and-websocket.md) |
| Check PHP and Phalcon in each runtime | [Runtime Requirements](phalcon-runtime-upgrades.md) |
| Test your application's behavior | [Application Testing](application-testing.md) |
| Resolve a failure | [Troubleshooting](troubleshooting.md) |

## Existing Applications

Use the [migration index](migrations/README.md) for package/namespace changes,
older REST resources, Core 4, and App layout/dependency changes. These guides
share a consistent preparation, change, verification, and rollback structure.

## Reference And Help

- [Environment variables](reference/environment.md): every variable Core reads,
  its config path, and its default.
- [Services](reference/services.md): the DI services Core registers, their
  providers, and how to replace them.
- [CLI commands](reference/cli.md): built-in tasks, their actions, and options.
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
