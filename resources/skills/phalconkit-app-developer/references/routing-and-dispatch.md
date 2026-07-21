# PhalconKit Routing And Dispatch

Use this reference when changing routes, module routing, locale/hostname
routing, CLI or WebSocket routes, dispatcher plugins, CORS/preflight behavior,
maintenance mode, request helpers, response headers, or route-related errors.

## Phalcon Baseline

Native Phalcon references:

- Routing: https://docs.phalcon.io/5.17/routing/
- Controllers: https://docs.phalcon.io/5.17/controllers/
- Dispatcher API: https://docs.phalcon.io/5.17/api/phalcon_mvc/#mvcdispatcher
- Request: https://docs.phalcon.io/5.17/request/
- Response: https://docs.phalcon.io/5.17/response/

PhalconKit configures and extends native Phalcon routing and dispatcher
behavior. Use native docs for route matching, dispatcher parameters, forwards,
request accessors, and response handling.

## Router Defaults

Default MVC router config lives under `router.defaults`:

```php
'router' => [
    'defaults' => [
        'namespace' => 'App\\Modules\\Frontend\\Controllers',
        'module' => 'frontend',
        'controller' => 'index',
        'action' => 'index',
    ],
],
```

`PhalconKit\Mvc\Router`:

- Removes extra slashes.
- Reads defaults and not-found config.
- Mounts `ModuleRoute`.
- Can add hostname routes.
- Can mount module routes from registered modules.
- Exposes route state through `toArray()`.

## Module Routes

`PhalconKit\Mvc\Router\ModuleRoute` creates conventional routes:

```text
/{module}
/{module}/{controller}
/{module}/{controller}/{action}/{params}
```

When locale config has allowed locales, it also creates locale routes:

```text
/{locale}/{module}
/{locale}/{module}/{controller}
/{locale}/{module}/{controller}/{action}/{params}
```

Hostname routes use the configured hostname instead of the `/{module}` prefix.
Route names use the module name or a slug generated from the hostname.

## CLI And WebSocket Routes

CLI defaults:

```php
'router' => [
    'cli' => [
        'namespace' => 'App\\Modules\\Cli\\Tasks',
        'module' => 'cli',
        'task' => 'help',
        'action' => 'main',
    ],
],
```

WebSocket defaults:

```php
'router' => [
    'ws' => [
        'namespace' => 'App\\Modules\\Ws\\Tasks',
        'module' => 'ws',
        'task' => 'main',
        'action' => 'listen',
    ],
],
```

Both CLI and WS routers expose route state through `toArray()`. Use this for
debugging, logging, and tests.

## Error Routes

Core config defines route targets for:

- `notFound`
- `httpException`
- `fatal`
- `forbidden`
- `unauthorized`
- `maintenance`
- `error`

Keep app overrides explicit:

```php
'router' => [
    'notFound' => [
        'namespace' => 'App\\Modules\\Frontend\\Controllers',
        'module' => 'frontend',
        'controller' => 'error',
        'action' => 'notFound',
    ],
    'httpException' => [
        'namespace' => 'App\\Modules\\Api\\Controllers',
        'module' => 'api',
        'controller' => 'error',
        'action' => 'error',
    ],
],
```

`PhalconKit\Exception\HttpException` is an expected request failure. The MVC
dispatcher accepts its exception code only when it is in the 400-599 range,
sets the shared response with the standard `PhalconKit\Http\StatusCode` reason
phrase, and forwards it through `router.httpException` in both debug and
production modes. Unmapped in-range codes use the framework-owned generic `Bad
Request` or `Internal Server Error` phrase for their status category. Invalid
codes become HTTP 500. An arbitrary exception with a numeric code such as `403`
remains an unexpected HTTP 500 failure.

The forward preserves the HttpException under the named `exception` route
parameter and the normalized status under `code`. Applications may override
the route or error controller to normalize application message contracts, but
status validation remains framework-owned. The bundled API error controller
exposes the exception message as a standard `view.messages` entry; bundled
Frontend/Admin pages keep the status and reason phrase without rendering raw
exception details.

Unexpected exceptions are rethrown in debug mode. In production they use
`router.fatal`, return HTTP 500, and do not expose the exception message or
trace. Native missing-controller/action exceptions continue to use
`router.notFound` and HTTP 404.

For single-page frontends, the app error controller can forward unknown
frontend routes to the compiled frontend entrypoint while still returning API
errors from API controllers.

## Dispatcher Plugins

Important dispatcher plugins:

- `Security`: checks ACL permissions for controllers/tasks/actions.
- `Preflight`: sets CORS headers and exits with 204 for preflight requests.
- `Maintenance`: forwards to the configured maintenance route when enabled.
- `Logger`: logs dispatch metadata when dispatcher logging is enabled.
- `Camelize`: available as a listener class, but not registered by default.
  Treat it as a pending design decision unless an application opts into it
  explicitly.

Rules:

- Keep action/controller permissions in config, not scattered in controllers.
- Configure CORS headers under `response.corsHeaders`.
- Configure maintenance through `app.maintenance` and `router.maintenance`.
- Do not add route-specific CORS logic unless the app has a real exception.

## Security Dispatch

The dispatch security plugin:

1. Builds ACL components from `components` plus `controllers` or `tasks`.
2. Checks the current handler class and action.
3. Allows any request when no `permissions` config is present.
4. Forwards to `unauthorized` when an identity has more than one ACL role but
   none are allowed.
5. Forwards to `forbidden` when the current identity has no allowed role.

If the handler class is not an ACL component, it forwards to `notFound`.

## CORS And Preflight

`PhalconKit\Http\Request` adds:

- `isCors()`
- `isPreflight()`
- `isSameOrigin()`
- `toArray()`

`Preflight` reads `response.corsHeaders`:

```php
'response' => [
    'corsHeaders' => [
        'Access-Control-Allow-Origin' => ['https://app.example.com'],
        'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS, PUT, PATCH, DELETE',
        'Access-Control-Allow-Headers' => 'Origin, Content-Type, Authorization, X-Authorization',
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age' => '600',
    ],
],
```

Rules:

- Use exact origins for credentialed browser clients when possible.
- Avoid `*` with credentials in production browser flows.
- Let the preflight plugin return 204 for OPTIONS preflight requests.

## Dispatch Logging

Dispatcher logging is enabled when app/logger config enables logging and
`logger.dispatcher` is true. The dispatch log includes:

- identity key
- user id
- impersonated user id
- dispatcher route state from `toArray()`

Do not log raw request bodies or authorization tokens in dispatch logs.
