# Migration From zemit-cms/core

Phalcon Kit Core is the continuation of the package previously published as
`zemit-cms/core`.

Use this guide if you maintain an older application that still depends on the
old package name.

## What Changed

- New package name: `phalcon-kit/core`
- Old package name: `zemit-cms/core`
- New repository: `https://github.com/phalcon-kit/core`
- Old Packagist package: https://packagist.org/packages/zemit-cms/core

The old package remains useful for historical releases and existing installs.
New projects should use `phalcon-kit/core`.

## When To Migrate

Do not rename the package in a production app casually. Migrate when you can run
the app test suite and verify the API flows that matter.

Good times to migrate:

- during a planned dependency update;
- while upgrading PHP or Phalcon;
- while moving to the `1.x` Phalcon Kit line;
- when you already need to regenerate model scaffolding or update REST
  controllers.

Avoid migrating during an urgent bug fix or deploy window.

## Composer Change

In `composer.json`, replace the old package with the new one:

```json
{
  "require": {
    "phalcon-kit/core": "^1.1"
  }
}
```

Then update:

```shell
composer update phalcon-kit/core --with-dependencies
```

If the app currently requires `zemit-cms/core`, remove that requirement in the
same change. Do not require both package names.

## Namespace And Code Changes

Most modern code should use `PhalconKit\...` namespaces. If the application
still references old `Zemit\...` namespaces, search and replace those references
intentionally:

```shell
rg "Zemit\\\\|zemit-cms|zemit"
```

Update imports, config provider class names, and any app docs/scripts that name
the old package.

## Things To Verify

After the Composer change:

```shell
composer validate --strict --no-check-publish
composer qa
```

At minimum, manually verify:

- bootstrap and DI provider registration;
- database connection and migrations;
- generated model class names and aliases;
- REST list/detail/save endpoints;
- authentication and JWT/session behavior;
- permission-protected API endpoints;
- CLI tasks;
- WebSocket worker, if used.

## Recommended Migration Commit

Keep the migration commit focused:

- Composer package rename.
- Updated imports/config references, if needed.
- README or deploy script references to the new package.
- Lock-file update.

Avoid mixing the rename with unrelated refactors. It should be easy to review
and revert if a legacy app exposes an unexpected compatibility issue.

## New Project Rule

For new projects, use the
[`phalcon-kit/app`](https://packagist.org/packages/phalcon-kit/app) skeleton:

```shell
composer create-project phalcon-kit/app my-api
```

or:

```shell
composer require phalcon-kit/core
```
