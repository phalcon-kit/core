# How A Request Runs

Every PhalconKit entrypoint builds the same application: one bootstrap reads
your config, registers services from providers, registers modules, and then
runs in one of three modes. Knowing the order tells you where a setting is
read, where to extend behavior, and which layer produced an error.

## The Three Entrypoints

The App skeleton has one entrypoint per mode. Each loads `bootstrap.php`
(path constants and the Composer autoloader) and runs `App\Bootstrap`:

| Entrypoint | Mode | Handles |
| --- | --- | --- |
| `public/index.php` | `Bootstrap::MODE_MVC` | HTTP requests through the web server |
| `bin/phalcon-kit` | `Bootstrap::MODE_CLI` | `./bin/phalcon-kit cli <task> <action>` commands |
| `bin/websocket` | `Bootstrap::MODE_WS` | The Swoole WebSocket worker |

```php
$bootstrap = new Bootstrap(Bootstrap::MODE_CLI);
echo $bootstrap->run();
exit($bootstrap->getExitCode());
```

All three share your config, providers, models, permissions, and services. A
service you register once is available to controllers, CLI tasks, and
WebSocket tasks.

## 1. Build The Application

`new Bootstrap($mode)` composes the application before any request is read:

1. **DI container.** A Phalcon `FactoryDefault` container (`FactoryDefaultCli`
   in CLI mode) becomes the default DI, with the bootstrap itself registered as
   the `bootstrap` service.
2. **`initialize()`.** Your bootstrap's hook runs first. The App skeleton sets
   `App\Config` here, so your config replaces Core's defaults before anything
   reads them.
3. **Config.** The `config` service returns that config. Core's defaults live in
   `PhalconKit\Bootstrap\Config`; your `App\Config` extends it and merges your
   values on top. Most defaults read an environment variable; see
   [Environment Variables](reference/environment.md).
4. **Providers.** Core instantiates every class in the `providers` config, in
   order, and calls its `register()` method. Each provider registers one DI
   service, such as `db`, `identity`, `acl`, or `dispatcher`; most are
   registered lazily and built on first use. See [Services](reference/services.md).
5. **Boot.** The `debug` service is resolved and the listeners in the
   `eventsManager.listeners` config are attached.
6. **Modules and router.** The modules in the `modules` config are registered
   on the application (MVC), console (CLI), or WebSocket application, with
   `router.defaults.module` as the default module. The `router` service is
   resolved last.

A missing or invalid provider class fails here with a `ConfigurationException`,
before any route is matched.

## 2. Run The Request

`$bootstrap->run()` hands the request to the mode's application:

| Mode | Application | Input |
| --- | --- | --- |
| MVC | `application` service | `$_SERVER['REQUEST_URI']` |
| CLI | `console` service | `$_SERVER['argv']`, after global options such as `--format` are parsed |
| WS | `webSocket` service | Starts the Swoole server; each message is routed to a WebSocket task |

In CLI mode, `run()` returns the task output and `getExitCode()` returns 1 when
the command threw an exception. When a long-running worker calls `run()` again
on the same bootstrap, request-scoped state (the decoded identity, ACL roles)
is reset first; see [Web Servers And WebSockets](web-server-and-websocket.md).

## 3. Route To A Module

The router matches the URI (or the CLI arguments) to a module, a controller or
task, and an action. The module's `Module.php` sets the namespace where
handlers are looked up, so `/api/project/find` in the App skeleton resolves to
`App\Modules\Api\Controllers\ProjectController::findAction()`, and
`cli user help` resolves to `App\Modules\Cli\Tasks\UserTask::helpAction()`.

## 4. Dispatch Through The Listeners

The `dispatcher` service attaches Core listeners to the `dispatch` events.
They run in event order, before your controller code:

| Event | Listener | What it does |
| --- | --- | --- |
| `beforeDispatchLoop` | Security | Checks the ACL for the handler class and action. A handler no permission names goes to `router.notFound`; a denied identity goes to `router.unauthorized` or `router.forbidden`. |
| `beforeDispatchLoop` | Logger | Logs the resolved route when logging is enabled. |
| `beforeDispatch` | Maintenance | Forwards to `router.maintenance` while `app.maintenance` is true. |
| `beforeExecuteRoute` | Preflight | Adds the configured CORS headers and answers preflight `OPTIONS` requests with 204. |
| `beforeForward` | Module | Keeps the module name in step when a listener forwards. |
| `beforeException` | Error (MVC only) | Maps a missing controller or action to `router.notFound`, and other exceptions to the error routes. |

The ACL roles come from the identity: every request has `everyone`, plus
`guest` or the signed-in user's roles, plus `cli` in CLI mode and `ws` in
WebSocket mode. See [Identity And Permissions](identity-and-permissions.md).

## 5. Run The Action

After the listeners, Phalcon calls the action. A REST controller then applies
its own policies: which fields a caller may filter, sort, or include, the row
conditions from permissions, and which fields it exposes in the response. See [REST Controllers](rest-api.md) and
[Requests And Responses](rest-requests-and-responses.md) for the response
envelope and error codes.

A CLI task returns a value instead of writing a response; the task formats it
with `--format` (JSON by default). See [CLI Tasks](cli-tasks.md).

## Where To Extend

| You want to | Change |
| --- | --- |
| Set a value used by a service | An environment variable, or the config path in `App\Config` |
| Add an application service | A provider in the `providers` config |
| Replace a Core service | Map Core's provider class to yours in `providers` |
| React to dispatch or model events | A listener in the `eventsManager.listeners` config |
| Add a module or change routes | The `modules` and `router` config |
| Grant or restrict an action | The `permissions` config or controller permission attributes |

See [Configuration](configuration.md) for each of these, and
[Application Structure](architecture.md) for where the code lives.
