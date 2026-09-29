# Runtime Requirements

Run the application with a PHP/Phalcon combination satisfying its Composer
requirements. The current package requires PHP 8.5 or later and Phalcon
`^5.22.0`. Composer's platform checks are the authoritative installation check.

## Check The CLI Runtime

```shell
php --version
php --ini
php -r 'echo "PHP ", PHP_VERSION, " / Phalcon ", phpversion("phalcon") ?: "missing", PHP_EOL;'
composer check-platform-reqs
```

The native extension supplies Phalcon classes at runtime. `phalcon/ide-stubs`
provides editor/analyzer signatures and does not install or load the extension.
Keep the IDE stubs compatible with the native version you test.

## Check Every Process

| Process | Verify |
| --- | --- |
| Composer and CLI tasks | PHP executable and loaded CLI ini files |
| PHP-FPM | Pool's PHP binary, extension directory, ini, environment, worker user |
| Containers | PHP image, built extension ABI, runtime environment |
| WebSocket/queue workers | Supervisor command, environment, restart after dependency/config changes |
| Tests | Same native extension behavior as the deployed application |

A CLI success does not prove PHP-FPM has Phalcon loaded. Check a private runtime
health endpoint or deployment diagnostics without leaving a public `phpinfo()`
page. Restart long-lived workers after replacing code/config/extensions.

## Database Runtime

Use the correct PDO extension and database connection options. MySQL and MariaDB
can differ in collations, SQL modes, JSON functions, and spatial/regexp support.
Use [Configuration](configuration.md#mysql-and-mariadb) for a MariaDB driver and
validate the operators your API exposes on your actual database.

Keep transaction boundaries paired. Before optional rollback cleanup, check
`isUnderTransaction()` so cleanup does not hide the original exception. Native
model casts, decimal types, nullable values, and eager loading should be verified
with save/reload tests, not only static analysis.

## Install Reproducibly

Commit the application lockfile and install it in CI/deployment:

```shell
composer install --no-interaction --prefer-dist
composer check-platform-reqs
composer phpunit
```

Use `--no-dev` for a runtime artifact only when development tools are not needed
there. The skeleton's migration runner is a development dependency; run migrations
from an artifact/environment that includes it. Never use `--ignore-platform-reqs`
as proof of runtime compatibility.

## Metadata And Caches

After schema changes and scaffold regeneration, clear/rebuild the application's
model metadata and relevant caches, then restart persistent workers. Native
Phalcon annotations can use docblocks or an attributes reader; choose the reader
before caching metadata. Attribute and docblock value types can differ, so test
custom metadata strategies against the model definitions you ship.

Use [Troubleshooting](troubleshooting.md) when one runtime succeeds and another
fails. Historical release changes remain in the [changelog](../CHANGELOG.md).
