# Quality And Maintenance

Use these commands before opening pull requests or cutting releases.

## Local Tool Setup

Use PHP 8.5+, Phalcon 5.22.x, Composer 2.10+, and the extensions declared in
`composer.json`. The QA wrappers call executables on `PATH`; installing this
package's development dependencies does not install every analyzer or PHPUnit.

The maintenance checks were verified with PHPUnit 12.5.34, PHPStan 2.2.9,
Psalm 6.16.1, and PHPCS 4.0.4. One reproducible local setup is to keep each tool's
Composer dependencies isolated from the library and from the other tools:

```shell
qa_tools="$HOME/.local/share/phalcon-kit-qa"
mkdir -p "$qa_tools"/{phpunit,phpstan,psalm,phpcs}
composer --working-dir="$qa_tools/phpunit" require phpunit/phpunit:12.5.34
composer --working-dir="$qa_tools/phpstan" require phpstan/phpstan:2.2.9
composer --working-dir="$qa_tools/psalm" require vimeo/psalm:6.16.1
composer --working-dir="$qa_tools/phpcs" require squizlabs/php_codesniffer:4.0.4
export PATH="$qa_tools/phpunit/vendor/bin:$qa_tools/phpstan/vendor/bin:$qa_tools/psalm/vendor/bin:$qa_tools/phpcs/vendor/bin:$PWD/vendor/bin:$PATH"
composer install
composer skeleton
```

Run the final two commands from the Core checkout. `pds-skeleton` comes from its
development dependencies. Existing PHAR installations or shared tool directories
on `PATH` are also suitable; check tool versions when reproducing a failure.
These versions are a verified local baseline, not additional runtime dependencies.

Full compatibility checks require the development extensions, including Imagick
and Swoole. If those two extensions are unavailable locally, a scoped
`composer install --ignore-platform-req=ext-imagick --ignore-platform-req=ext-swoole`
can support partial development checks. Tests for the missing runtimes may skip;
the required CI compatibility jobs still need to run before release.

## Local Gates

Run the full gate before merging:

```shell
composer qa
```

Run focused gates while developing:

```shell
composer qa:composer
composer qa:style
composer qa:static
composer qa:security
composer qa:test
```

Direct aliases are also available:

```shell
composer phpcs
composer phpstan
composer psalm
composer psalm:taint
composer phpunit
```

## What Each Gate Covers

- `qa:composer`: validates Composer metadata and runs dependency audit.
- `qa:style`: runs PHPCS.
- `qa:static`: runs PHPStan and Psalm.
- `qa:security`: runs Psalm taint analysis.
- `qa:test`: runs PHPUnit.

The wrapper scripts under `bin/` keep local and CI analyzer behavior aligned.

## CI Expectations

The default CI workflow runs on the declared PHP baseline. Composer and PHPUnit
jobs test both lowest and highest dependencies; style and static-analysis jobs
use highest dependencies. It checks:

- Composer validation and audit.
- PHPCS.
- PHPStan.
- Psalm with GitHub Code Scanning SARIF upload.
- Psalm taint analysis.
- PHPUnit with MySQL and Redis services.

Password reset, relationship ownership, boolean persistence, and native aggregate
regressions also run in a dedicated step with `--fail-on-skipped` in both dependency
jobs. This prevents a missing integration setting from silently removing coverage.

Separate workflows run OpenSSF Scorecard and zizmor for repository and GitHub
Actions hygiene.

## Disposable Database Tests

The four native database regressions opt in through `PHALCONKIT_TEST_DB_SOCKET`
or `PHALCONKIT_TEST_DB_HOST` (the socket wins when both are set). Point either
variable at a dedicated disposable MySQL/MariaDB server with a passwordless
`root` test account allowed to create and drop schemas. Each test creates a random
schema and drops it in a `finally` block. These settings are separate from the
application's normal database configuration.

```shell
PHALCONKIT_TEST_DB_SOCKET=/path/to/disposable/mysql.sock composer phpunit -- \
  tests/Unit/Identity/PasswordResetDatabaseTest.php \
  tests/Unit/Mvc/Model/RelationshipAssignmentDatabaseTest.php \
  tests/Unit/Mvc/Model/BooleanPersistenceDatabaseTest.php \
  tests/Unit/Mvc/Model/AggregateDatabaseTest.php \
  --fail-on-skipped --display-errors
```

CI supplies `PHALCONKIT_TEST_DB_HOST=127.0.0.1` for its MySQL service. Existing
`PHALCONKIT_RESET_TEST_SOCKET`, `PHALCONKIT_RELATION_TEST_SOCKET`, and
`PHALCONKIT_BOOLEAN_TEST_HOST` commands remain supported by their original tests.
Without an opt-in setting, these tests skip in the lightweight local suite.

## Comment And Deprecation Maintenance

Document public and protected contracts where consumers extend or configure them:
purpose, meaningful input shapes, results, exceptions, required services, side
effects, and override expectations. Explain non-obvious decisions and limitations.
Keep simple getters concise and use inherited contracts when behavior is unchanged.
Correct stale comments and runnable examples before adding more prose.

Document source declarations and consumer guides directly. Regenerate API output
only as an intentional documentation build. Unresolved behavior belongs in
[To Be Discussed](to-be-discussed.md), with compatibility risks and a validation
plan, rather than vague TODO comments.

These deprecated Core aliases remain available in the Core 4.0 development
line. Update consumers and routes to their replacements before considering
removal in a future major release:

| Deprecated API | Replacement |
| --- | --- |
| Model `with()` | `findWith()` with the same array arguments |
| Model `firstWith()` | `findFirstWith()` with the same array arguments |
| REST `getAction()` | `findFirstAction()` |
| REST `getWithAction()` | `findFirstWithAction()` |
| REST `getAllAction()` | `findAction()` |
| REST `getAllWithAction()` | `findWithAction()` |
| `NestedNativeArray::exists()` | `has()` |

Keep the legacy Phalcon interface types allowlisted in
`tests/Unit/Support/PhalconDeprecationTest.php` while native override signatures
require them. The test detects upstream boundary changes; deleting those types
based only on deprecation markers can break class loading. Public extension
shells and exception subclasses also need a consumer review before removal.

## Generated Artifacts

Generated or local-only artifacts should not be committed:

- `vendor/`
- `.phpunit.cache/`
- `.phpdoc/`
- `docs/` generated API output
- `results.sarif`
- `taints.dot`
- local environment files

The release archive excludes QA configs, tests, stubs, local caches, and agent
root-level maintainer instructions that are not needed at runtime. Optional
assets under `resources/`, including skills, can be shipped intentionally when
they are part of the package documentation surface.

## Changelog Discipline

Update `CHANGELOG.md` under the current unreleased section when a change affects
public behavior, compatibility, security posture, generated output, QA tooling,
or maintainer workflow.

Use [Release Process](release.md) when preparing a tag.

## Planning Discipline

Keep planning documents separated by purpose:

- `ROADMAP.md` tracks active, schedulable release blocks only.
- `guides/to-be-discussed.md` tracks design questions that are not ready for a
  release block.
- `CHANGELOG.md` tracks completed public behavior, compatibility, security,
  tooling, and maintainer workflow changes.
- User-facing guides and shipped skills keep durable usage guidance after a
  feature ships.

After a release, refresh the roadmap target and remove completed blocks once
the changelog and relevant guides contain the outcome. Avoid keeping historical
`Done` sections in the roadmap; they make the next actionable block harder to
see.
