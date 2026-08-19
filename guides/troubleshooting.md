# Troubleshooting

Start from the observed symptom, reproduce it with the smallest entrypoint, and
inspect the layer that owns that behavior. Phalcon Kit uses the same configured
services across HTTP, CLI, and WebSocket modes, but each process can still load
a different environment or PHP configuration.

## First Five Checks

Run these before changing application code:

```bash
php --version
php -r 'echo phpversion("phalcon") ?: "phalcon not loaded", PHP_EOL;'
composer check-platform-reqs
composer validate --strict --no-check-publish
git status --short
```

Then confirm the process has the expected environment:

```bash
php --ini
php -r 'var_export([getcwd(), getenv("APP_ENV"), getenv("DATABASE_HOST")]);'
```

Never print passwords, tokens, private keys, or full environment dumps into CI
logs or public issues.

## Application Does Not Boot

### `Class ... not found`

Check in this order:

1. The namespace matches the file path and Composer PSR-4 mapping.
2. `composer dump-autoload` completes.
3. The app loader registers the application namespace before bootstrap.
4. A renamed Zemit class is not still referenced.
5. The failing process uses the current deployed vendor directory.

```bash
composer dump-autoload -o
rg 'Zemit\\|zemit-cms' app config public cli websocket
```

### A provider fails during bootstrap

Providers must define a non-empty service name and receive a
`PhalconKit\Di\DiInterface` container. Inspect the provider configured as the
key and implementation value:

```php
'providers' => [
    \PhalconKit\Provider\Identity\ServiceProvider::class =>
        \App\Provider\Identity\ServiceProvider::class,
],
```

If a custom bootstrap supplies its own DI, use `PhalconKit\Di\Di`,
`PhalconKit\Di\FactoryDefault`, or `PhalconKit\Di\FactoryDefault\Cli` rather
than a native-only container.

## A Service Is Missing Or Has The Wrong Type

Confirm three things:

1. The provider appears in app config.
2. The provider registers the same stable service name callers use.
3. The resolved object implements the contract expected by the caller.

Use typed resolution at composition boundaries:

```php
$logger = $di->getTyped('logger', \Phalcon\Contracts\Logger\Logger::class);
```

Avoid fixing a type mismatch with an unchecked cast or by widening every
consumer to `mixed`. The service definition or provider override is usually the
real ownership point.

## Routes Return 404 Or The Wrong Action

Check the complete route-to-dispatch path:

- the web server points to `public/`;
- unknown files are forwarded to `public/index.php`;
- the expected module is registered and selected;
- controller namespaces match the module configuration;
- action names follow the route convention;
- permission failures are not being mistaken for route misses.

For REST resources, remember that route action keys are dash-case—such as
`find-with` or `archive-project`—while PHP methods are camelCase, such as
`findWithAction()` and `archiveProjectAction()`.

```bash
curl --include http://127.0.0.1:8000/api/project
```

Inspect the status, response envelope, and debug output in a development
environment. Do not enable detailed exception output in production.

## Database Or Migration Failures

### The app connects to the wrong database

Compare the environment seen by the exact process that fails. CLI migrations
and PHP-FPM may load different `.env` files or working directories.

Verify host, database, username, port, and adapter without printing the
password. Then run the project’s migration list command before applying
anything.

### Generated models do not match the schema

The scaffolder reads the live database, not the migration source files. Confirm
the migration ran against the intended database, then regenerate and inspect
the diff:

```bash
./bin/migration-list.sh
./bin/migration-run.sh
./vendor/bin/phalcon-kit cli scaffold run --src-dir=app/ --namespace=App
git diff -- app/Models
```

Do not hand-edit generated abstract models to hide drift. Correct the migration
or scaffolding configuration and regenerate.

## REST Requests Behave Unexpectedly

### A field cannot be saved, filtered, or exposed

REST capabilities are allowlisted separately. Check the controller initializer
for the exact operation:

- `initializeSaveFields()`
- `initializeFilterFields()`
- `initializeSearchFields()`
- `initializeExposeFields()`
- `initializeWith()`
- `initializeDistinctActionFields()`

A field being safe to expose does not automatically make it safe to save or
filter.

### A relationship causes many queries

Use `findWith()`/`findFirstWith()` for a server-selected graph or the native
eager parameter where appropriate. Confirm the relationship alias in the
generated model and do not request the same graph through both mechanisms.

### Counts differ from list length

Pagination, grouping, joins, and embedded count policy can intentionally make
these values differ. Distinguish:

- page length;
- normal `count`;
- grouped bucket count;
- `bucketTotal`;
- ungrouped `totalCount`.

Read [REST APIs](rest-api.md) before changing count SQL in an application
controller.

## Permission Problems

Separate feature policy from row policy:

- feature/role policy decides whether an action is available;
- permission conditions restrict which rows the query returns;
- behavior overrides modify query behavior for a specific feature or role.

When debugging, record the controller, route action, normalized feature name,
active roles, and symbolic permission-condition keys. Do not log JWTs, session
IDs, password hashes, or personal record contents.

If super roles see data but normal users do not, inspect row-level conditions
before adding broader feature grants.

## HTTP Works But CLI Or WebSocket Fails

Compare:

```bash
which php
php --ini
php -m
pwd
```

Also compare the working directory, environment file, mounted secrets, and
deployed release path. Long-lived workers must be restarted after code,
configuration, or extension changes; PHP-FPM reloads do not restart a separate
Swoole process.

## Static Analysis And Runtime Disagree

The usual causes are:

- IDE stubs do not match the loaded Phalcon extension;
- the analyzer uses a different PHP version;
- generated code is stale;
- a test double exposes a broader or narrower signature than runtime;
- caches contain results from a previous dependency graph.

Clear only tool-specific caches, then rerun the smallest failing command. Do
not delete the lock file or vendor directory as a first diagnostic step.

## Before Opening An Issue

Prepare a minimal, non-sensitive report:

- expected and actual behavior;
- smallest reproducing code or request;
- PHP and Phalcon versions from the failing process;
- installed Phalcon Kit package version;
- database adapter when relevant;
- exception class and sanitized stack trace;
- whether the failure occurs in HTTP, CLI, WebSocket, or all modes;
- checks already attempted.

Security vulnerabilities belong in the private process described by
`SECURITY.md`, not a public issue.

Continue with [Runtime Compatibility](phalcon-runtime-upgrades.md) for platform
alignment or [Quality And Maintenance](quality-and-maintenance.md) for the full
project validation gate.
