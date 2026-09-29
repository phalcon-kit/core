# Application Migration Guides

Use these guides when moving an existing application between package, layout,
or API contracts. For a new application, start with [Getting Started](../getting-started.md).
For SQL schema changes within your application, use [Database Migrations](../database-migrations.md).

## Choose The Guides That Apply

| Your application | Guide | Result |
| --- | --- | --- |
| Requires `zemit-cms/core` or imports `Zemit\` | [Package and namespace migration](from-zemit.md) | PhalconKit dependency and application wiring |
| Overrides the old 0.x REST getters or expects `single`/`list` | [REST resource migration](rest-0x.md) | Current model generation, policies, routes, and response contract |
| Uses Core before 4.0 | [Core 4 migration](core-4.md) | Removed runtime dependencies handled; application data/history preserved |
| Uses the App 1.x `app/` layout | [App layout migration](app-2.md) | `src/`, `public/`, `bin/`, and application-owned modules |
| Uses App 2.x or an older Core dependency in its copied skeleton | [App 4 migration](app-4.md) | Current runtime, Core dependency, and application helpers |

Several guides may apply. Inventory the application first, update its package
and bootstrap wiring, preserve its model/business code, then migrate resources
one at a time. An App 1.x project needs the layout changes as well as the App 4
changes. A Core 3.x project does not need the old REST migration if it already
uses the current initializer API.

## Shared Preparation

- Record the installed package versions, PHP/Phalcon versions, and application commit.
- Preserve the application lockfile, deployment configuration, database backup,
  and already-applied migration history.
- Work in a separate checkout/environment with disposable or approved test data.
- Record representative requests, response shapes, permission denials, and
  persisted results before making changes.
- Plan application code, database, and client changes separately. A dependency
  update does not convert data or update already-deployed clients.

Each guide uses the same sections: **Applies To**, **Before You Start**,
**Changes To Apply**, **Verify**, **Rollback**, and **Related Guides**. Follow the
current usage guides for full feature recipes; migration guides explain what
must change from the stated starting point.

The target runtime and package constraints come from Composer metadata. Use
[Runtime Requirements](../phalcon-runtime-upgrades.md) and the
[security policy](../../SECURITY.md) when selecting a supported target.
