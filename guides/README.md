# Guides

These guides are for developers who want to build Phalcon applications faster
with Phalcon Kit. Start with the task you are trying to complete.

## I Want To Build A REST API

1. [Build Your First REST Resource](first-rest-resource.md): build a resource
   from schema to controller, permissions, and request/response examples.
2. [REST APIs](rest-api.md): learn the REST controller policy methods.
3. [Models And Eager Loading](models-and-eager-loading.md): load relation graphs
   without lazy-loading loops.
4. [Identity And Permissions](identity-and-permissions.md): add role and
   row-level access.

## I Want To Start Or Integrate A Project

1. [Getting Started](getting-started.md): install, bootstrap, run locally.
2. [Configuration](configuration.md): configure modules, providers, aliases, and
   permissions.
3. [Architecture](architecture.md): understand where app code, generated code,
   modules, and tasks belong.

## I Want To Generate Models From A Database

1. [Database And Scaffolding](database-scaffolding.md): run migrations and
   scaffold generated model layers.
2. [Models And Eager Loading](models-and-eager-loading.md): add concrete model
   behavior and relationship loading.
3. [Build Your First REST Resource](first-rest-resource.md): connect generated
   models to a REST controller.

## I Want To Deploy Or Maintain The Package

1. [Web Server And WebSocket](web-server-and-websocket.md): PHP-FPM, web roots,
   and WebSocket worker proxying.
2. [Quality And Maintenance](quality-and-maintenance.md): local QA commands and
   CI expectations.
3. [Project Roadmap](../ROADMAP.md): release blocks, priorities, retired
   GitHub Project items, and maintainer planning rules.
4. [Testing Roadmap](testing-roadmap.md): phased unit, component, integration,
   model, eager-loading, and REST API test coverage plan.
5. [To Be Discussed](to-be-discussed.md): open maintainer design questions that
   need a concrete use case before behavior changes.
6. [Release Process](release.md): release checklist and package-history notes.

## I Am Using zemit-cms/core

Read [Migration From zemit-cms/core](migration-from-zemit.md) before changing
package constraints. The short version: new projects use `phalcon-kit/core`;
older projects should stay pinned until the migration can be tested.

If the application also uses old 0.x RESTful controllers, read
[Migrate RESTful 0.x Resources To 1.x](migration-restful-0x-to-1x.md) for the
controller, scaffolding, eager-loading, permission, and custom-action migration.

## Official Phalcon Docs

Phalcon Kit extends Phalcon instead of replacing it. Use the official Phalcon
docs for native framework behavior and these guides for PhalconKit conventions.

- Phalcon docs: https://docs.phalcon.io/5.13/
- Phalcon framework: https://phalcon.io

## Agent References

Read [AI-Assisted Development](../AI.md) for the bundled skill paths, usage
examples, safety defaults, and coverage notes.

Agent-specific instructions live under `resources/skills/`. Human readers
should start here in `guides/`; agents can use the skills for stricter coding
rules and deeper implementation conventions. The human docs and skill
references should stay aligned on the same public concepts: database-first
scaffolding, REST controllers, eager loading, transformers, identity,
permissions, CLI/WebSocket workflows, providers, and generated-file boundaries.
