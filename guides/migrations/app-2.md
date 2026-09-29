# Migrate The App Layout

## Applies To

| Starting point | Target | Scope |
| --- | --- | --- |
| App 1.x applications using `app/` and root runtime helpers | The `src/`/`public/`/`bin/` layout introduced in App 2 and used by current App | Copied application files, autoloading, entrypoints, modules, and deployment paths |

Use [App 4 migration](app-4.md) for the current runtime and Core dependency after
applying these layout changes. Installing a newer skeleton does not rewrite an
existing application's copied files.

## Before You Start

Complete the [shared preparation](README.md#shared-preparation). Inventory web
server roots, cron/supervisor commands, container entrypoints, deployment scripts,
and test/bootstrap files that reference the old paths. Preserve concrete models,
custom controllers/tasks, secrets, runtime files, and migration history.

## Changes To Apply

### 1. Move Application Classes And Autoloading

| Old location | Current location |
| --- | --- |
| Application classes under `app/` | `src/` under the same `App\` namespace |
| `App\Config\Config` | `App\Config` in `src/Config.php` |
| Runtime entrypoints at project root | HTTP in `public/`; CLI/WebSocket in `bin/` |
| Migration/generation helpers in `bin/` | `scripts/` |
| Mixed temporary/log paths | Application-managed `storage/` subdirectories |

Update the Composer mapping while preserving the application's other mappings:

```json
{"autoload":{"psr-4":{"App\\":"src/"}}}
```

Update Config imports in bootstrap, devtools, tests, and custom entrypoints, then
run `composer dump-autoload -o`.

### 2. Normalize Bootstrap And Entrypoints

Use the root `bootstrap.php` and entrypoint examples in
[Application Integration](../application-integration.md). Paths include their
trailing separator; `APP_PATH` identifies `ROOT_PATH . 'src/'`.

- HTTP: `public/index.php`, with **only `public/`** exposed by the web server.
- CLI: `bin/phalcon-kit`, bootstrapped in `MODE_CLI`.
- Optional WebSocket worker: `bin/websocket`, bootstrapped in `MODE_WS`.
- Migration and model generation: application `scripts/` helpers, which resolve
  the project root from their own location.

Update every server/scheduler/deployment caller before retiring old entrypoint
files. Keep an explicit compatibility wrapper temporarily if callers cannot
switch in the same deployment.

### 3. Register Application Modules And Permissions

Compare copied module classes with current App. Keep application-owned Frontend,
API, Admin, CLI, and optional WS namespaces registered in config. The WS worker
needs `router.ws.namespace` and its task permission. Current App grants only its
listener to the `ws` context and does not publish Admin to anonymous users.

Review inherited permissions against the application's actual access contract.
Add Core CLI task bridges deliberately; see [CLI Tasks](../cli-tasks.md).
Install Swoole only for a runtime that uses the optional worker.

## Verify

Run the application checks, one HTTP read/write, a denied request, and CLI help
from both the project root and another working directory. Test Linux/PowerShell
helpers used by your team. Confirm private source, `.env`, and `storage/` are
outside the web document root. Test optional worker startup and protocol behavior
with the application's real supervisor configuration.

## Rollback

Restore the prior application artifact and its matching server/scheduler paths
together. Avoid serving a mixture of new entrypoints and old class locations.
A layout move should not alter data or migration history; roll back any separate
schema changes through their own recovery procedure.

## Related Guides

- [App 4 migration](app-4.md)
- [Application integration](../application-integration.md)
- [Architecture](../architecture.md)
- [Web servers and WebSockets](../web-server-and-websocket.md)
