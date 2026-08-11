# Runtime Compatibility

Phalcon Kit documentation supports the latest stable package release. The
package’s `composer.json`, CI workflow, and release notes are the authorities
for exact PHP, Phalcon extension, and development-tool versions.

Use this guide to verify that an application’s runtime matches those declared
requirements without duplicating version numbers in application documentation.

## Compatibility Has Several Layers

A working installation aligns all of these surfaces:

| Layer | Source of truth | Why it matters |
| --- | --- | --- |
| PHP runtime | `composer.json` | Language features and extension ABI |
| Native Phalcon extension | `ext-phalcon` constraint | Runtime classes and behavior |
| IDE/analyzer stubs | `phalcon/ide-stubs` constraint | Static signatures and completion |
| Application lock file | `composer.lock` | Reproducible dependency graph |
| Container images | Docker build arguments | Production/runtime parity |
| CI setup | Workflow install and cache keys | Proof on a clean environment |

Changing only one layer can produce misleading results—for example, an IDE may
accept a method that the loaded extension does not provide, or CLI PHP may load
a different extension than PHP-FPM.

## Inspect The Declared Requirements

From the application root:

```bash
composer show phalcon-kit/core
composer show phalcon/ide-stubs 2>/dev/null || true
composer check-platform-reqs
```

To inspect the installed native runtime directly:

```bash
php --version
php -r 'echo phpversion("phalcon") ?: "not installed", PHP_EOL;'
php --ri phalcon
```

!!! warning "Check every PHP runtime you actually use"

    CLI, PHP-FPM, queue workers, and Swoole/WebSocket processes can load
    different `php.ini` files. Run the extension check inside each production
    container or process environment instead of assuming they match.

## Install Or Update An Application

Evergreen install instructions deliberately omit a Phalcon Kit version:

```bash
composer require phalcon-kit/core
```

Composer selects the newest release compatible with the application’s PHP and
platform extensions. Applications that commit `composer.lock` should review and
commit the resulting lock-file change.

For a focused update:

```bash
composer update phalcon-kit/core phalcon/ide-stubs --with-dependencies
composer check-platform-reqs
```

Do not use `--ignore-platform-reqs` as a permanent install strategy. A targeted
ignore can help prepare metadata before a native extension is installed, but
the final environment must pass `composer check-platform-reqs` without ignores.

## Upgrade The Core Package Runtime

When maintainers change the supported runtime, keep the work in one reviewable
slice:

1. Update PHP, `ext-phalcon`, and stub constraints in `composer.json`.
2. Update Docker build arguments and base images.
3. Update CI installers, download URLs, and extension cache keys.
4. Review upstream release notes for changed and removed APIs.
5. Search source, tests, examples, and patches for affected symbols.
6. Refresh dependency metadata and run the complete QA gate.
7. Record concrete compatibility changes in `CHANGELOG.md`.

Avoid mixing the runtime bump with unrelated model, schema, or API behavior.
That separation makes failures attributable and makes downstream upgrades easier
to review.

## Review Framework Boundaries

Native runtime changes most often affect these integration points:

- DI container and service-provider contracts;
- events manager and event contract names;
- request, response, cookies, and session behavior;
- model relationships, eager loading, resultsets, and database metadata;
- router and dispatcher method signatures;
- validation, filtering, and message collections;
- debug rendering and error handling;
- PHPDoc/stub signatures used by Psalm, PHPStan, and IDEs.

Search both code and documentation. A compatibility fix is incomplete if the
runtime works but public examples still teach removed APIs.

```bash
rg 'Phalcon\\|ext-phalcon|phalcon/ide-stubs|PHALCON_VERSION' \
  composer.json src tests guides .github Dockerfile*
```

Adjust the paths for the repository. Review every result rather than applying a
blind namespace replacement.

## Validate In Increasing Scope

Start with fast checks:

```bash
php -m | rg '^phalcon$'
composer validate --strict --no-check-publish
composer check-platform-reqs
git diff --check
```

Then run package quality gates:

```bash
composer phpcs
composer psalm
composer psalm:taint
composer phpunit
composer skeleton
```

Finally, prove the application paths that static analysis cannot cover:

- bootstrap one HTTP request;
- run migrations against a disposable database;
- execute one representative model relationship query;
- create and update a model with validation enabled;
- run one CLI task;
- boot any long-lived worker or WebSocket process;
- build the production container from a clean cache.

## Diagnose A Mismatch

| Symptom | Likely cause | First check |
| --- | --- | --- |
| Composer reports a missing `ext-phalcon` | CLI PHP does not load the extension | `php --ini` and `php --ri phalcon` |
| IDE accepts a method but runtime fails | Stub/runtime versions differ | Compare Composer stubs with `phpversion('phalcon')` |
| Web works but CLI fails | Different PHP binary or INI | `which php`, `php --ini`, FPM config |
| CI recompiles Phalcon every run | Cache key or installed-version check is stale | Workflow extension cache and installer step |
| Container works locally but not in deployment | Different image digest/build argument | Inspect the deployed image metadata |

Continue with [Troubleshooting](troubleshooting.md) for application-level boot,
DI, routing, database, and REST symptoms. Maintainers should also follow
[Quality And Maintenance](quality-and-maintenance.md) and
[Release Process](release.md).
