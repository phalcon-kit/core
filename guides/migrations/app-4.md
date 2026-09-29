# Migrate To App 4

## Applies To

| Starting point | Target | Scope |
| --- | --- | --- |
| App 2.x or an application copied from an older skeleton | Current App conventions with Core 4.x | Runtime/dependencies, copied helpers, explicit maintenance, and application acceptance |

App moved from 2.x to 4.x to align its major version with Core. For an App 1.x
layout, also apply [App layout migration](app-2.md). Your copied application
files remain yours; a Composer update does not replace them automatically.

## Before You Start

Complete the [shared preparation](README.md#shared-preparation). Compare your
application files with current [App](https://github.com/phalcon-kit/app), preserving
custom modules, model hooks, migrations, secrets, and deployment behavior.

Review [Core 4 migration](core-4.md) before changing the dependency. Applications
that already use initializer-based REST policies do not need to repeat the 0.x
resource conversion.

## Changes To Apply

### 1. Align Runtime And Dependencies

Use PHP 8.5+, native Phalcon satisfying `^5.22.0`, matching development stubs,
and Core `^4.0`. Update and inspect the application lockfile. Verify all runtime
processes, including CLI, PHP-FPM, and long-running workers.

Use the current App Composer configuration as a reference for migration-tool
development dependencies, reviewed patches, and patch lockfile. Keep the tools
available in the environment that executes application migrations; deployment
runtime packages and migration tooling may be installed in separate artifacts.

### 2. Review Copied Helper Commands

| Area | Current behavior to adopt |
| --- | --- |
| Migration runner | Standalone `phalcon-migrations run`, without an extra `migration` command word |
| Model generation | Generate missing concrete classes; regenerate schema layers while preserving concrete business logic |
| Migration generation | No automatic overwrite flag; database references are connection-local |
| Script locations | Root-relative helpers under `scripts/`, Unix and PowerShell variants |
| Core CLI tasks | Application namespace bridges and explicit CLI permissions |

Follow [Database Migrations](../database-migrations.md),
[Scaffolding](../database-scaffolding.md), and [CLI Tasks](../cli-tasks.md) for
commands. Review copied scripts instead of replacing customized files blindly.

### 3. Keep Application Data And History

Do not copy the fresh `resources/migrations/4.0.0` Core baseline into an existing
application's pending migrations. Keep every already-applied application
migration, including an older empty `1.0.0` placeholder if it is recorded.

Review OAuth token widths, identifier lengths, indexes, engines, and collations
against actual application requirements through explicit application migrations.
The fresh baseline describes a new database, not a conversion script.

### 4. Make Maintenance And Account Setup Explicit

Core's maintenance tasks have empty table/seed defaults. Configure only the
application tables and seed models needed by each operation. No account or role
is seeded implicitly. Use the [CLI account workflow](../authentication.md#create-an-account-with-the-cli)
with an explicit private password and application role grants.

## Verify

Run `composer qa`, HTTP resource and authentication checks, CLI commands from
another working directory, and applicable WebSocket tests. With disposable data,
verify model save/reload, nested writes, permission denials, migration listing,
and application-owned maintenance instructions. Exercise browser/mobile cookie,
CORS, and refresh behavior in the deployment configuration.

Confirm the committed lockfile matches the tested dependencies, copied helper
paths match deployment callers, and existing migration history is preserved.

## Rollback

Restore the previous application code, lockfile, runtime, and copied helper
configuration as one release artifact. Use `composer install` with that lockfile.
Keep database changes separate and reverse them only using their reviewed
recovery procedure. Do not remove recorded migrations to make an old version
appear compatible.

## Related Guides

- [Core 4 migration](core-4.md)
- [App layout migration](app-2.md)
- [Database migrations](../database-migrations.md)
- [Authentication](../authentication.md)
- [Application tests](../application-testing.md)
