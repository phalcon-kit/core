# Developer Guides

Build a complete Phalcon Kit application one workflow at a time. These guides
document the latest stable `phalcon-kit/core` release; Composer metadata is the
authority for runtime and dependency requirements.

!!! tip "New to Phalcon Kit?"

    Follow the **First API** track in order. It starts with a runnable app and
    ends with a database-backed, permission-aware REST resource.

## Choose A Learning Track

<div class="grid cards" markdown>

-   :material-rocket-launch:{ .lg .middle } **First API**

    ---

    Install the application, learn its structure, and build a complete resource.

    1. [Getting Started](getting-started.md)
    2. [Resource Walkthrough](resource-walkthrough.md)
    3. [Build Your First REST Resource](first-rest-resource.md)

-   :material-database-cog:{ .lg .middle } **Database-First Development**

    ---

    Turn migrations into typed models, relationships, validation, and API data.

    1. [Database And Scaffolding](database-scaffolding.md)
    2. [Models And Eager Loading](models-and-eager-loading.md)
    3. [REST APIs](rest-api.md)

-   :material-shield-account:{ .lg .middle } **Secure APIs**

    ---

    Configure identity, roles, feature permissions, and row-level conditions.

    1. [Identity And Permissions](identity-and-permissions.md)
    2. [Configuration](configuration.md)
    3. [Developer Cookbook](cookbook.md)

-   :material-server-network:{ .lg .middle } **Run And Operate**

    ---

    Serve HTTP, run CLI and WebSocket processes, and validate runtime alignment.

    1. [Web Server And WebSocket](web-server-and-websocket.md)
    2. [Runtime Compatibility](phalcon-runtime-upgrades.md)
    3. [Troubleshooting](troubleshooting.md)

-   :material-swap-horizontal:{ .lg .middle } **Migrate**

    ---

    Move a legacy package or REST resource onto current namespaces and contracts.

    1. [From zemit-cms/core](migration-from-zemit.md)
    2. [RESTful 0.x To 1.x](migration-restful-0x-to-1x.md)

-   :material-hammer-wrench:{ .lg .middle } **Contribute**

    ---

    Run the project gates, understand test layers, and prepare releases.

    1. [Quality And Maintenance](quality-and-maintenance.md)
    2. [Testing Strategy](testing-roadmap.md)
    3. [Release Process](release.md)

</div>

## Core Concepts

Use these guides as the durable reference while building:

| Topic | What it answers |
| --- | --- |
| [Architecture](architecture.md) | Where should bootstrap, config, generated code, models, controllers, and tasks live? |
| [Configuration](configuration.md) | How do modules, providers, aliases, events, identity, and permissions fit together? |
| [Database And Scaffolding](database-scaffolding.md) | Which files are generated, which files are app-owned, and how do schema changes flow into code? |
| [Models And Eager Loading](models-and-eager-loading.md) | How do relationships, nested saves, eager loading, behaviors, snapshots, and cache invalidation work? |
| [REST APIs](rest-api.md) | How are fields, filters, joins, counts, transformers, and response envelopes configured? |
| [Identity And Permissions](identity-and-permissions.md) | How are feature access, roles, attributes, behaviors, and row-level policy enforced? |

## Examples And Answers

- [Developer Cookbook](cookbook.md) contains focused copy-and-adapt recipes for
  common application tasks.
- [Troubleshooting](troubleshooting.md) starts from symptoms such as boot
  failures, missing services, routing errors, migration drift, and unexpected
  REST output.
- [API Reference](https://phalcon-kit.github.io/docs/api/) is generated from the
  current source and is useful when you know the class or method name.

## Latest-Version Policy

This documentation is intentionally rolling:

- it supports the latest stable Phalcon Kit release;
- it does not maintain parallel versioned sites;
- evergreen examples use unconstrained install commands;
- exact versions belong in Composer metadata, release notes, and compatibility
  investigations where the number itself matters.

Phalcon Kit extends Phalcon instead of replacing it. Use the
[latest Phalcon documentation](https://docs.phalcon.io/latest/){:target="_blank"}
for native framework behavior. Phalcon Kit is independently maintained and is
not affiliated with or endorsed by the official Phalcon project.

## AI-Assisted Development

Read [AI-Assisted Development](https://github.com/phalcon-kit/core/blob/master/AI.md) for bundled skill paths, safe usage,
and coverage notes. Human-facing guides explain the concepts; agent references
under `resources/skills/` add stricter operational instructions. Both surfaces
should describe the same public conventions.
