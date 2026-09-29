# Migrate From zemit-cms/core

## Applies To

| Starting point | Target | Scope |
| --- | --- | --- |
| Applications requiring `zemit-cms/core` or importing `Zemit\` | `phalcon-kit/core` with `PhalconKit\` namespaces | Dependency, imports, bootstrap, modules, and provider/model registrations |

The package rename alone does not update old REST controllers or replace the
runtime removed in Core 4. Follow the linked guides for those changes as well.

## Before You Start

Complete the [shared preparation](README.md#shared-preparation). Check the current
[PHP/Phalcon requirements](../phalcon-runtime-upgrades.md) before resolving the
new dependency. Capture a working HTTP request and CLI command on the old code.

Search application-owned files, excluding installed dependencies:

```shell
rg -n 'Zemit\\|zemit-cms/core|vendor/zemit-cms' src app config public bin scripts composer.json
```

Run the search only against directories present in your project. Include custom
bootstrap files, worker entrypoints, deployment scripts, and Composer scripts.

## Changes To Apply

### 1. Replace The Composer Dependency

Remove the `zemit-cms/core` requirement and add the current Core requirement in
`composer.json`:

```json
{"require":{"phalcon-kit/core":"^4.0"}}
```

This is a fragment: keep the application's other requirements. Do not require
both packages. Align PHP, the native extension, and development stubs with
Core's requirements, then resolve and inspect the lockfile:

```shell
composer update phalcon-kit/core --with-all-dependencies
composer validate --strict --no-check-publish
composer check-platform-reqs
```

### 2. Update Application Wiring

| Existing reference | Current reference |
| --- | --- |
| `Zemit\Bootstrap` | `PhalconKit\Bootstrap` |
| `Zemit\Bootstrap\Config` | `PhalconKit\Bootstrap\Config` |
| `Zemit\Bootstrap\Devtools` | `PhalconKit\Bootstrap\Devtools` |
| `Zemit\Modules\Api\Module` | `PhalconKit\Modules\Api\Module` |
| `Zemit\Mvc\Module`, `Zemit\Cli\Module` | Matching `PhalconKit\` module classes |
| Provider overrides and service registrations | Corresponding current class after checking its interface |
| Core model aliases and registry keys | Current retained Core model class mapped to the application implementation |

Review each symbol against the installed class reference. A namespace change is
not proof that a class still exists or has the same signature. Keep application
namespaces and concrete business logic application-owned.

Use [Application Integration](../application-integration.md) for the current
entrypoints, path constants, Composer autoloading, and DI contracts. If changing
the copied App layout too, follow [App layout migration](app-2.md).

### 3. Handle API And Removed-Feature Changes

- Convert old policy getters and response conventions with [REST migration](rest-0x.md).
- Review every removed model/controller/provider in [Core 4 migration](core-4.md).
- Preserve the existing application's tables and migration history. Do not run
  the fresh Core baseline over that database.

## Verify

Run the application's checks and repeat its recorded HTTP/CLI requests. Verify
bootstrap, provider resolution, model aliases, list/detail/write results,
authentication, role/row permissions, and optional WebSocket workers. Search
again for unintended old package paths/imports, including deployment files.

Inspect the lockfile to confirm it resolves `phalcon-kit/core`. A successful
Composer install alone does not prove application behavior.

## Rollback

Restore the application code and its matching lockfile together, then run
`composer install` in the rollback artifact. Restore matching runtime/deployment
configuration if it changed. Namespace/dependency edits do not themselves require
a database rollback; handle any separately applied SQL changes using their own
verified recovery procedure.

## Related Guides

- [REST resource migration](rest-0x.md)
- [Core 4 migration](core-4.md)
- [App layout migration](app-2.md)
- [Application integration](../application-integration.md)
