# Phalcon Runtime Upgrades

Use this guide when moving a PhalconKit application or the core package to a
new native Phalcon patch or minor release.

This is separate from the `zemit-cms/core` package rename and the old RESTful
resource migration. Runtime upgrades are mostly dependency, extension,
static-analysis, CI, and compatibility-review work.

## When To Use This

Use this checklist when changing any of these values:

- the installed `phalcon` PHP extension;
- the Composer `ext-phalcon` platform requirement;
- `phalcon/ide-stubs`;
- Docker `PHALCON_VERSION` build arguments;
- CI Phalcon install URLs or extension cache keys.

Keep those changes in one focused commit where possible. Avoid mixing a runtime
upgrade with unrelated model, controller, schema, or API behavior changes.

## Phalcon 5.16.0 Checklist

For the 5.16.0 line, align the package and runtime on:

```json
{
  "require": {
    "ext-phalcon": "^5.16.0"
  },
  "require-dev": {
    "phalcon/ide-stubs": "^5.16.0"
  }
}
```

Applications that keep `phalcon/ide-stubs` only under `suggest` or in a
separate development tooling package should still update the same version floor
there so IDE and analyzer signatures match the installed extension.

## Local Runtime

Install the native extension first, then verify the CLI PHP runtime that
Composer and QA tools will use:

```shell
php -r 'echo phpversion("phalcon") ?: "not installed"; echo PHP_EOL;'
php --ri phalcon
composer check-platform-reqs
```

If a machine has multiple PHP binaries, run those checks with the same binary
used by Composer, PHP-FPM, Swoole workers, and CLI tasks. Do not rely on web
server PHP and CLI PHP having the same extension version unless both are
checked.

## Composer And Stubs

For PhalconKit core, update tracked Composer constraints and leave ignored
local lock/vendor changes out of the release commit unless the repository
explicitly tracks them.

For applications that track `composer.lock`, update the lock file too:

```shell
composer update phalcon/ide-stubs phalcon-kit/core --with-dependencies
composer check-platform-reqs
```

When preparing Composer metadata before the new extension is installed, the
temporary lock refresh can ignore only the not-yet-installed platform
requirements:

```shell
composer update phalcon/ide-stubs --with-dependencies --ignore-platform-req=ext-phalcon
```

Add other `--ignore-platform-req` flags only for extensions that are unrelated
to the upgrade and genuinely absent from the local CLI PHP runtime. Run
`composer check-platform-reqs` again after the real extension is installed.

If the application does not update `phalcon-kit/core` in the same change, still
update `phalcon/ide-stubs` so Psalm, PHPStan, and IDEs analyze against the same
native API version as the runtime.

## Docker And CI

Update every runtime image and CI pin that installs Phalcon:

- Docker `ARG PHALCON_VERSION`;
- GitHub Actions or other CI `PHALCON_VERSION`;
- PECL or GitHub release tarball URLs;
- extension cache keys that include the Phalcon version;
- image tags or build cache keys derived from PHP and Phalcon versions.

After changing CI pins, confirm the install URL resolves before relying on the
workflow. A redirect from the GitHub release asset URL is enough for the
installer used by this repository.

## Application Compatibility Review

Patch-level Phalcon upgrades are usually small, but PhalconKit applications
should review these recurring integration boundaries:

- Replace deprecated `Phalcon\Events\ManagerInterface` and
  `Phalcon\Events\EventInterface` references in application-owned contracts with
  `Phalcon\Contracts\Events\Manager` and `Phalcon\Contracts\Events\Event`.
- Keep native Phalcon setter boundaries honest. If a native method still
  requires a concrete `Phalcon\Events\Manager`, guard or type the value there
  instead of passing only a broader contract.
- Keep mailer config canonical and lower-case, especially
  `MAILER_SMTP_ENCRYPTION=ssl` or `tls`. PhalconKit normalizes common casing,
  but lowercase config keeps app examples and deploy variables unambiguous.
- Validate app config for provider options that become network behavior later,
  such as mailer driver, SMTP encryption, host, port, username, and adapter
  class names.
- If overriding REST/query policy setters or merge helpers, keep signatures
  widened to the current `array|\Phalcon\Support\Collection|null` contracts.
- If using `modelHasColumn()`, keep application PHPDoc aligned with its nullable
  model-name contract. The helper returns `false` for missing or invalid model
  names instead of requiring a strict `class-string`.

## Validation

Run the smallest useful checks before the extension is installed, then run the
runtime checks after installation.

Before installing the new extension:

```shell
composer validate --strict --no-check-publish
git diff --check
```

After installing the new extension:

```shell
composer check-platform-reqs
composer phpcs
composer psalm
composer psalm:taint
composer phpunit
```

For a public release, run the full release gate from
[Quality And Maintenance](quality-and-maintenance.md) and follow
[Release Process](release.md).
