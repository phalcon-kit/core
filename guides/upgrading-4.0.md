# Upgrading To Core 4.0

Status: unreleased development work on `master`. This guide describes implemented
changes and the checks still required before a stable release. PHP 8.5 and
Phalcon 5.22 remain the runtime baseline.

Only Core 4.x is maintained. All earlier versions, including `zemit-cms/core`,
are end of life and receive no support, bug fixes, security fixes, or backports.
There is currently no supported stable release while Core 4.0 is in development.
Older tags remain available for reproducible installs during migration; see
the [security policy](../SECURITY.md).

Core 4.0 focuses on reusable Phalcon extensions and common application features.
Application schemas belong to the applications that use them. This change
retires the old spreadsheet/catalog application and CMS runtime while keeping
the existing provider, model-mapping, controller, and module extension patterns.

## What Stays

- Bootstrap, configuration, providers, MVC/CLI/WebSocket modules, and helpers.
- REST controllers, query/filter/save rules, response transformers, model
  behaviors, eager loading, nested relationship writes, and scaffolding.
- Identity, login/registration/reset hooks, sessions, impersonation, permissions,
  roles/groups/types, email/templates, files, audit, and settings.
- Locale, translation adapters, validation helpers, and ordinary database models.
- Generic database maintenance commands and the existing REST compatibility
  aliases. Migrate callers of deprecated routes separately with response tests.

The remaining prepared models (`Backup`, `Job`/`JobScheduler`, `Profile`,
`FileRelation`, and feature/association models) stay in this first scope. Some
are referenced by retained services or model relationships; absence of an
application import alone is insufficient evidence for removing them.

Core remains one package. No new repository abstraction or optional legacy
package is introduced by this change.

## Removed Model Inventory

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

## Removed Runtime Surface

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

The dependency review includes retained models' relationship targets, through
models, service/config references, and application inheritance. Tests initialize
all retained Core models and resolve their relationship classes without opening
a database. This verifies the runtime class graph, not the availability of any
application's database tables.

## Explicit Database Maintenance

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
explicitly through the application's account-creation flow; do not recreate the
old default development account or assume that seed insertion generates a
secure password.

## Existing Data And Migration History

No removal in this branch runs a migration, drops a table, or deletes data.
Keep application-owned migration history and existing schemas intact while
upgrading PHP code. Deleting unused database tables is a separate application
migration with its own data review and rollback plan.

`resources/migrations/1.0.0/` remains the historical complete schema, unchanged.
It includes retired tables and hard-coded `phalcon_kit` foreign-key schemas.
The maintainer migration scripts still target that history; it is not a minimal
Core 4.0 fresh-install recipe. Do not run it against an existing app as a cleanup
step. A portable, feature-based fresh-install path is a stable-release gate.

See [migration schema portability](to-be-discussed.md#baseline-migration-schema-portability)
for the unresolved schema/upgrade contract. Retained models still have real
storage requirements; removing the catalog does not make identity, audit, or
other persisted features schema-free.

## Application Upgrade Checks

1. Keep the application's existing lockfile and a suitable tagged-version
   constraint while preparing an isolated upgrade checkout. `dev-master` now
   follows breaking 4.0 development; a dependency update can select it. The old
   `0.4.x`, `1.0.x`, and temporary `4.x` branches have been retired. Applications
   using branch constraints must select an appropriate tagged release or opt
   into testing `dev-master` deliberately.
2. Search imports, parent classes, interfaces, DI registrations, model mappings,
   permission config, routes, CLI schedules, and seed code for the exact retired
   namespaces above. Remove unused imports and permission-only references too.
3. Preserve app-owned schemas and adapt any actual Core-domain dependencies.
   Applications that still need the retired runtime must own the equivalent
   feature before upgrading. Remaining on 3.x means using an unsupported release
   without security fixes or backports. No compatibility shim is provided.
4. Supply explicit database-maintenance instructions. Verify app overrides,
   seed models, and empty defaults with disposable data.
5. Test authentication and reset delivery, authorization, model substitution,
   REST response shapes, nested saves, eager loading, audit/files, and any CLI or
   WebSocket tasks the application uses. Class-loading smoke tests do not prove
   those flows work.

## Stable Release Gates

The first implementation removes the closed legacy runtime group and implicit
maintenance data. It does not claim a completed application migration.

Before 4.0 is tagged:

- Provide and test the supported fresh-install schema path with a custom database
  name; validate existing-schema upgrades without rewriting historical migrations.
- Align the app skeleton and document each retained feature's required models,
  tables, services, and supported substitution contract.
- Exercise real consumer acceptance suites in isolated checkouts, including
  custom identity/session overrides, reset delivery, REST compatibility routes,
  and WebSocket tasks.
- Review the remaining direct concrete-model lookups and generated-interface
  coupling before promising full model substitution across every feature.
- Pass the release CI matrix, mandatory native database regressions, and a fresh
  Composer install; regenerate API documentation deliberately from the final
  retained public surface.
- Publish the upgrade notes with the 4.x-only support policy in `SECURITY.md`.
  `master` is the sole long-lived branch; signed tags identify
  releases. Complete branch-consumer migration checks before recommending 4.0
  for existing applications.
