# PhalconKit Logging And Observability

Use this reference when changing logger config, named loggers, SQL logging,
profiler behavior, dispatch logging, debug rendering, CLI task logs, or
WebSocket logs.

## Phalcon Baseline

Native Phalcon references:

- Logger: https://docs.phalcon.io/5.17/logger/
- Events manager: https://docs.phalcon.io/5.17/events/
- Database/model events: https://docs.phalcon.io/5.17/db-models-events/
- Debug tools: https://docs.phalcon.io/5.17/debug/

PhalconKit logging uses native logger, events, database events, and debug
concepts as its base. Use native docs for logger adapters and event manager
behavior; use this file for PhalconKit named loggers, profiler wiring, dispatch
metadata, and no-log rules.

## Logger Services

Core services:

- `logger`: the default logger.
- `loggers`: registry for named loggers.
- `profiler`: database profiler service.
- `debug`: debug/exception rendering service.

The default logger is loaded through the `loggers` registry:

```php
$this->logger->info('message');
$this->loggers->get('database')->info('query log');
```

## Config Shape

Default logger config:

```php
'logger' => [
    'enable' => Env::get('LOGGER_ENABLE', false),
    'drivers' => [
        'noop' => \Phalcon\Logger\Adapter\Noop::class,
        'stream' => \Phalcon\Logger\Adapter\Stream::class,
        'syslog' => \Phalcon\Logger\Adapter\Syslog::class,
    ],
    'formatters' => [
        'json' => \Phalcon\Logger\Formatter\Json::class,
        'line' => \Phalcon\Logger\Formatter\Line::class,
    ],
    'default' => [
        'driver' => 'stream',
        'formatter' => 'line',
        'path' => STORAGE_PATH . '/log/',
        'filename' => 'default',
    ],
],
```

Named loggers live under `loggers`:

```php
'loggers' => [
    'database' => [
        'enable' => Env::get('LOGGER_DATABASE_ENABLE', false),
        'driver' => 'stream',
        'formatter' => 'line',
        'filename' => 'database.log',
    ],
],
```

Built-in named logger keys include:

- `error`
- `database`
- `request`
- `dispatcher`
- `profiler`
- `mailer`
- `cron`
- `auth`

## Dispatcher Logging

The dispatcher logger plugin writes dispatch metadata when logging is enabled
and dispatcher logging is enabled.

Logged metadata includes:

- identity key
- user id
- impersonated user id
- dispatcher route state

Do not add request bodies, authorization headers, cookies, or JWTs to dispatch
logs.

## Database Logger And Profiler

Database events connect queries to logging and profiling.

Use cases:

- Enable `loggers.database.enable` to inspect SQL in development or controlled
  diagnostics.
- Enable profiler config for timing and query profiles.
- Keep database logging disabled by default in production unless there is a
  specific retention and privacy policy.

Rules:

- Avoid logging query bind values that may include secrets or personal data.
- Prefer temporary, scoped SQL logging for diagnosis.
- Do not leave verbose database logging enabled in hot paths without a reason.

## Debug

The debug provider and config can render exceptions with selected context.
Core debug config includes a blacklist for common secret names.

Rules:

- Keep debug exception pages disabled in public production environments.
- Extend blacklist patterns when an app adds new credential names.
- Do not paste debug pages into public issues if they include paths, env data,
  SQL, tokens, or customer data.

## CLI And WebSocket Logs

CLI tasks and WebSocket tasks can use logger services or task-level `log()`
helpers depending on the module base class.

For long-running WebSocket tasks:

- Log process start/stop.
- Log channel type and ids, not full payloads with private data.
- Log validation failures and recoverable errors.
- Keep heartbeat/ping logs low-volume or disabled in production.

For CLI tasks:

- Use named loggers such as `cron` for scheduled jobs.
- Include enough context to identify the job and record id.
- Avoid dumping full records unless the task is explicitly diagnostic.

## What Not To Log

Never log:

- Plaintext passwords.
- Password hashes.
- JWTs or refresh tokens.
- Session keys.
- Authorization headers.
- Cookies.
- Service-account JSON.
- API keys, OAuth client secrets, or OpenAI keys.
- Raw uploaded files or full request bodies with user data.
