# Migrate To Core 4

## Applies To

| Starting point | Target | Scope |
| --- | --- | --- |
| An application on Core before 4.0 | Core 4.x | Removed catalog/CMS dependencies, explicit maintenance configuration, and application-owned schema history |

Generic REST, scaffolding, models, identity, permissions, providers, and
HTTP/CLI/WebSocket infrastructure remain available. This guide identifies the
removed application-domain runtime and the changes needed by consumers.

## Before You Start

Complete the [shared preparation](README.md#shared-preparation). Use PHP 8.5+
and the Phalcon extension satisfying `^5.22.0`, consistent across CLI, PHP-FPM,
and workers. Review [Feature Setup](../feature-contracts.md) for the tables,
services, and model contracts your application actually uses.

Inventory imported Core classes, model parents/interfaces, generated relation
targets, provider/model mappings, permissions, routes, CLI schedules, and seed
code. Do not infer dependency solely from short class names: an application may
own an unrelated `App\Models\Record` or `App\Models\Table`.

## Changes To Apply

### 1. Replace Removed Model Dependencies

All names in this table are under `PhalconKit\Models\`. For each name, the
concrete class, `Abstracts\{Name}Abstract`,
`Abstracts\Interfaces\{Name}AbstractInterface`, and
`Interfaces\{Name}Interface` are removed together.

| Retired area | Model families |
| --- | --- |
| Dynamic catalog | `Workspace`, `WorkspaceLang`, `Table`, `Column`, `Record`, `Data`, `Validator` |
| CMS | `Site`, `SiteLang`, `Page`, `Post`, `PostCategory`, `Category`, `Menu`, `Meta` |
| Database-backed language/translation records | `Lang`, `Translate` |
| Site-owned flags | `Flag` |

`Flag` belongs to the retired Site model graph. The separate `Feature` model
and feature permissions remain. `Models\Validator` was a catalog table model;
`PhalconKit\Filter\Validation` and its validators remain.

An application's `App\Models\Record`, `Data`, `Table`, or `Workspace` is not
removed. Inspect its parent, implemented interfaces, relations, and configuration
before deciding whether it depends on a retired Core contract. Ordinary models
can continue extending `PhalconKit\Mvc\Model` or
`PhalconKit\Models\AbstractModel` through application-owned generated abstracts.

### 2. Update Runtime References

| Surface | Exact removal | Application action |
| --- | --- | --- |
| API controllers in `PhalconKit\Modules\Api\Controllers` | `CategoryController`, `ColumnController`, `DataController`, `FlagController`, `LangController`, `MenuController`, `MetaController`, `PageController`, `PostController`, `RecordController`, `TableController`, `TranslateController`, `WorkspaceController`, plus the placeholder `FieldController`, `TranslateFieldController`, `TranslateTableController` | Remove obsolete routes/permissions, or implement an app-owned resource using the generic API controller. |
| Model implementation | `PhalconKit\Mvc\Model\Dynamic` | Use app-owned models with stable class/source metadata. There is no drop-in dynamic-source replacement. |
| Record transformer | `PhalconKit\Modules\Api\Transformers\RecordTransformer` | Use an application transformer for the application's record model. The generic transformer infrastructure remains. |
| Database provider | `PhalconKit\Provider\DatabaseDynamic\ServiceProvider`, service `dbd`, `database.drivers.dynamic`, `PROVIDER_DATABASE_DYNAMIC`, and `DATABASE_DYNAMIC_*` defaults | Remove unused configuration. If a second database is needed, register an application provider explicitly. Primary `db` and read-only `dbr` remain. |
| Permission presets in `PhalconKit\Bootstrap\Permissions` | `ColumnConfig`, `DynamicConfig`, `RecordConfig`, `TableConfig`, `WorkspaceConfig` | Remove these imports and ACL entries; define policies for app-owned resources. `TemplateConfig` remains. |
| Catalog fixture generator | `PhalconKit\Modules\Cli\Tasks\FakerTask` and `bin/database-faker.sh` | Remove task/permission references and scheduled invocations. Use application fixtures or explicit deployment seed rows. The generic Faker provider remains. |
| Enums in `PhalconKit\Models\Enums` | `ColumnType`, `WorkspaceStatus`, `SiteStatus`, `ValidatorType`, `TranslateTableTable` | Own any still-needed domain enums in the application. |
| Typed registry helpers | `getFlag()`, `getLang()`, `getTranslate()`, `getWorkspace()`, `getWorkspaceLang()`, `getPage()`, `getPost()`, `getTable()`, and their `get{Name}Class()` counterparts | Remove obsolete calls. `models` mappings and generic `getInstance()`/`getClassMap()` continue to support application-owned classes. |
| Default model mappings | Entries and `MODEL_*` defaults for those eight typed registry families | Remove overrides targeting the retired classes. Retained model mappings keep their current contracts. |

### 3. Make Database Maintenance Explicit

`database drop`, `truncate`, `fix-engine`, `insert`, `optimize`, `analyze`, and
`reset` remain available. Core no longer supplies table lists, role/language
seeds, or a development account. With no instructions, these commands perform
no database queries. `main` still runs engine changes, optimization, and analysis;
`reset` still truncates before inserting.

Put instructions in the application configuration, using its own table and
model names. For example:

```php
'deployment' => [
    'drop' => [],
    'truncate' => [],
    'engine' => ['app_lookup' => 'InnoDB'],
    'optimize' => ['app_lookup'],
    'analyze' => ['app_lookup'],
    'insert' => [
        \App\Models\Lookup::class => [
            ['key' => 'active', 'label' => 'Active'],
        ],
    ],
],
```

Each configured key replaces the matching task property in full. Omitted keys
preserve an application subclass's defaults, including properties assigned
before `parent::initialize()`. An explicit empty array disables that operation.
The six arrays may also be set directly on an application `DatabaseTask`.
`Bootstrap\Deployment` remains available as a config container with empty defaults.

These are trusted maintainer instructions, not request input. `drop` and
`truncate` destroy data, engine names must be trusted, and inserts are ordinary
model saves with their validation/events. Seeds use the concrete class key
exactly as supplied; Core-to-app model mappings are not applied here. The task
grants the CLI role access to the configured seed models. Repeated inserts are
not automatically idempotent, and reset is not an atomic database transaction.

Review existing seed records before copying them. Supply account credentials
explicitly through the [CLI account workflow](../authentication.md#create-an-account-with-the-cli); do not recreate the
old default development account or assume that seed insertion generates a
secure password.

### 4. Preserve Existing Data And Migration History

The package update does not run a migration, drops a table, or deletes data.
Keep application-owned migration history and existing schemas intact while
upgrading PHP code. Deleting unused database tables is a separate application
migration with its own data review and rollback plan.

The package now ships `resources/migrations/4.0.0/` for **fresh databases**,
with only the 29 retained Core tables. The old packaged `1.0.0/` directory is
removed; historical tags still contain it. Do not remove or rename migrations
already owned/applied by an application.

The baseline uses InnoDB, connection-local foreign keys, Unicode text defaults,
case-sensitive credential/token comparisons, larger OAuth token storage, and
64-character audit/file relation identifiers. It refuses existing tables/history
and never drops retired tables. Review these changes against actual application
data and introduce separate, explicit upgrade migrations where appropriate.

See [Database Migrations](../database-migrations.md) for the fresh installation and
reusable SQL helper. Retained models still require their tables; the baseline
contains no accounts, roles, or other seed records.

### 5. Resolve The Target Dependency

Require `phalcon-kit/core: ^4.0` in application Composer metadata, align native
Phalcon and development stubs, and update the lockfile in the prepared checkout:

```shell
composer update phalcon-kit/core --with-all-dependencies
composer check-platform-reqs
```

Inspect the resulting dependency changes. Applications that need removed
features must provide their own equivalent before switching runtime versions;
there is no drop-in compatibility package for the catalog/CMS runtime.

## Verify

- Run the application's checks, then initialize each retained/mapped model and
  exercise its real persistence and relationship paths.
- Verify authentication, reset delivery, sessions, role and row access, REST
  response shapes, nested writes, eager loading, and optional file/audit features.
- Prove unconfigured maintenance performs no writes. Test configured maintenance
  only against disposable application data and verify exactly which rows change.
- Run HTTP, CLI, and WebSocket entrypoints used by the application. Confirm
  schedules and worker commands resolve the intended task classes.
- Confirm existing migration history is unchanged and the fresh baseline was not
  added to pending application upgrades.

Class loading or package installation alone does not validate these workflows.

## Rollback

Keep a release artifact with the previous code, lockfile, runtime, and config.
Restore that set if application acceptance fails. The Core package update does
not drop application tables; any separate data conversion or cleanup needs its
own backup and reversible application migration. Do not use the fresh baseline
as a rollback mechanism.

## Related Guides

- [Feature setup](../feature-contracts.md)
- [Database migrations](../database-migrations.md)
- [REST resource migration](rest-0x.md)
- [App 4 migration](app-4.md)
- [Application tests](../application-testing.md)
