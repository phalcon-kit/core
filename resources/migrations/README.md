# Core Database Baseline

`4.0.0/` installs the 29 tables used by Core's built-in models.

Use this baseline **only for a fresh MySQL/MariaDB database**. It creates no
accounts, roles, or seed records. The application database name
is unrestricted: foreign keys refer to tables in the selected database.

`core.php` is one Phalcon migration extending the reusable
`PhalconKit\Migrations\SqlMigration` base. Each file in `sql/` contains one
`CREATE TABLE` statement, including its constraints. The wrapper runs them in
dependency order with foreign-key checks enabled. The standard migration runner
owns version tracking; `core` is not a table.

- All tables use InnoDB and `utf8mb4_unicode_ci` by default.
- UUIDs remain 36-character strings, stored as ASCII with case-insensitive UUID
  comparisons. IDs and foreign keys remain unsigned `BIGINT`.
- Credentials, session tokens, and OAuth subject IDs use `utf8mb4_bin` for
  case-sensitive comparisons. OAuth access/refresh tokens use `TEXT`.
- Stored table and column names accept 64 characters. Job identifiers use
  variable-length strings. Boolean `TINYINT(1)` metadata is retained; obsolete
  integer display widths have been removed elsewhere.
- The runner's empty `phalcon_migrations` table is normalized to InnoDB and
  the same text encoding. It is the thirtieth table when using `--log-in-db`.

The wrapper rejects existing tables/history, disabled foreign-key checks, and
active transactions. Re-running an already recorded version is a runner no-op.
MySQL DDL is not transactional: after a failed fresh install, inspect the error
and recreate that disposable database before retrying. Automatic destructive
rollback is deliberately unsupported.

Applications keep their own schema, migration history, and data. Copy the entire
`4.0.0/` directory into a fresh application's migration tree to take ownership;
do not copy it into an existing application's pending migrations. Once applied,
leave that copy immutable and make subsequent changes in new versions.

See [Database Migrations](../../guides/database-migrations.md) for installation,
reusable SQL examples, and tooling requirements. Core's `bin/migration-*.sh` helpers are maintainer
commands targeting this checkout's configured database.
