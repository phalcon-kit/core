# Quality And Maintenance

Use these commands before opening pull requests or cutting releases.

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

The default CI workflow runs on PHP 8.5 with highest dependencies. It checks:

- Composer validation and audit.
- PHPCS.
- PHPStan.
- Psalm with GitHub Code Scanning SARIF upload.
- Psalm taint analysis.
- PHPUnit with MySQL and Redis services.

Separate workflows run OpenSSF Scorecard and zizmor for repository and GitHub
Actions hygiene.

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
