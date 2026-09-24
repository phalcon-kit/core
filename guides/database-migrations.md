# Database Migrations

Use `phalcon/migrations` for version history and execution. Core's optional
`PhalconKit\Migrations\SqlMigration` base adds two protected helpers:
`executeSqlFile($path)` and `executeSqlFiles($orderedPaths)`. Each file contains
one complete SQL statement. There is no SQL parser, separate history table,
or second migration runner to configure.

The App skeleton already declares `phalcon/migrations` as a development
dependency. Other applications can install it with:

```shell
composer require --dev phalcon/migrations:^3.0.1
```

Keep migration tooling available in the environment that runs deployments;
`composer install --no-dev` does not install development-only tools. These
helpers accept trusted local files belonging to the application, never paths
or SQL supplied by an HTTP request.

## Fresh Core Installation

Core 4 ships a baseline for the retained models. It is opt-in; installing or
updating the Composer package does not run migrations. For a new App project
with an empty migration tree and a fresh database:

```shell
mkdir -p resources/migrations
cp -R vendor/phalcon-kit/core/resources/migrations/4.0.0 resources/migrations/
./scripts/migration-list.sh
./scripts/migration-run.sh
```

Set `DATABASE_DBNAME` and the database credentials in the application's `.env`
first. Create the database with an account that has the necessary privileges;
the migration runner uses an existing database. `devtools.php` must load the
application Composer autoloader before returning its database configuration,
as the App skeleton does.

Copy the whole version directory, including `core.php` and `sql/`. Run the whole
baseline; do not select individual table names with `--table`. Its `core.php`
wrapper represents one coordinated schema installation, not a table named
`core`. The runner records `4.0.0` only after the migration completes.

The baseline creates 29 Core tables plus the runner's `phalcon_migrations`
bookkeeping table with `--log-in-db`. It excludes the retired catalog/CMS tables
and creates no accounts or seed data. All tables use InnoDB, foreign keys stay
in the selected database, and checks remain enabled throughout installation.

Text defaults to `utf8mb4_unicode_ci`, which offers more accurate Unicode
comparisons than `general_ci` while remaining available in MySQL and MariaDB.
See the [MySQL collation documentation](https://dev.mysql.com/doc/refman/8.4/en/charset-unicode-sets.html).
Credentials/session tokens use case-sensitive `utf8mb4_bin`; UUIDs keep their
36-character public representation with ASCII storage. OAuth tokens use `TEXT`,
and stored table/column identifiers allow 64 characters. Other limits and
nullability retain the existing model contracts.

An existing table, nonempty migration log, active transaction, or disabled
foreign-key checks causes the baseline to stop before creating Core tables.
The runner may create its bookkeeping table while preparing an attempted run.
An already recorded baseline is skipped by the runner.

MySQL DDL commits implicitly. If installation fails partway through, investigate
and recreate the disposable fresh database before retrying; this is not an
atomic rollback. The baseline's `down()` refuses to delete application tables.

## Application-Owned SQL Migrations

For example, after adopting Core's baseline, create:

```text
resources/migrations/4.0.1/
  products.php
  sql/create-products.sql
```

`products.php`:

```php
<?php

declare(strict_types=1);

use PhalconKit\Migrations\SqlMigration;

class ProductsMigration_401 extends SqlMigration
{
    public function morph(): void
    {
        $this->executeSqlFile(__DIR__ . '/sql/create-products.sql');
    }

    public function down(): void
    {
        throw new RuntimeException('Restore a reviewed backup before removing products.');
    }
}
```

`sql/create-products.sql`:

```sql
CREATE TABLE `products` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(120) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

For multiple statements, put each in its own file and pass the paths in
execution order:

```php
$this->executeSqlFiles([
    __DIR__ . '/sql/create-products.sql',
    __DIR__ . '/sql/create-product-tags.sql',
]);
```

The helper reads and checks **all files before executing the first statement**.
SQL is sent unchanged to the configured connection. Semicolons inside strings
or stored-program bodies are preserved; client-only `DELIMITER` commands do not
belong in files passed to the database driver. Do not put several statements in
one file or rely on driver-specific multi-statement settings.

A missing, unreadable, or empty file raises `PhalconKit\Exception\RuntimeException`.
An execution failure stops subsequent statements; database exceptions propagate.
The helper does not start a transaction, disable foreign keys, automatically
undo earlier statements, or change migration history. Use the normal Phalcon
`up()`/`down()` hooks for data transformations or explicitly reviewed reversals.
Applied migration versions and their SQL files are immutable application history.

## Existing Applications

Keep existing migration files and data. Do not copy the fresh baseline into an
existing pending migration tree or rename recorded history from `1.0.0` to
`4.0.0`. The migration version sequence belongs to the application and need not
match package releases.

Review your current schema against the new definitions, then create explicit
application migrations for changes actually needed. In particular, review OAuth
token widths, 64-character audit/file relation identifiers, storage engines, and
credential comparisons. Changing text collations can change uniqueness rules;
check existing values before altering indexes. Retired tables are only removed
by an application-owned migration after its data/dependency review.

## PHP 8.5 Tool Compatibility

The current `phalcon/migrations` 3.0.1 release still declares some nullable
parameters implicitly. Core ships a type-only compatibility patch at
`patches/phalcon-migrations-php85.patch` and applies it in its development/CI
installation. It adds explicit `?` types without changing migration behavior.
Deprecation reporting remains enabled in Core's tests.

The matching App preview already configures Composer Patches, keeps reviewed
patch copies in its own `patches/` directory, and commits `patches.lock.json`.
Fresh development installs apply them automatically.

For a custom application, copy the reviewed patch from
`vendor/phalcon-kit/core/patches/phalcon-migrations-php85.patch` into the app's
versioned `patches/` directory. Register that local copy in its Composer patch
configuration, refresh the patch lock, and reinstall the migration dependency. Preserve the
application's existing patch configuration; paths in Core's own `composer.json`
are relative to the Core checkout, not the application root. Check the patch
against the installed migration version before upgrading the tool.

The SQL helper itself is optional: applications may keep their existing
Phalcon migration classes and use the new base only for migrations that benefit
from SQL files. Core does not install a migration runner as a production runtime
dependency.
