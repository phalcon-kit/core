# Release Process

Use this checklist when preparing a public release.

## Version Policy

Phalcon Kit follows SemVer for tagged public releases. Keep unreleased work
under the current `x.y.x - Unreleased` heading in `CHANGELOG.md` until the tag
is cut.

## Before Release

1. Confirm the target version and release branch.
2. Update `CHANGELOG.md` by moving the current unreleased section to a dated
   version heading.
3. Update runtime version metadata if needed.
4. Confirm the README and guides describe the release accurately.
5. Run the full local quality gate:

```shell
composer qa
```

6. Confirm Composer metadata:

```shell
composer validate --strict --no-check-publish
composer audit
```

7. Review public docs:
   - `README.md`
   - `CHANGELOG.md`
   - `SECURITY.md`
   - `SUPPORT.md`
   - `guides/`
8. Check package contents:

```shell
composer archive --format=tar --dir=/tmp
```

Review the archive if package exclusions changed.

## Tag

Use a SemVer tag:

```shell
VERSION=2.0.0
git tag "$VERSION"
git push origin "$VERSION"
```

Prefer signed tags when possible.

## After Release

1. Verify the GitHub Actions workflow passed on the tag/default branch.
2. Verify Packagist updated `phalcon-kit/core`.
3. Check GitHub Code Scanning for fresh Psalm results.
4. Confirm the old `zemit-cms/core` page still points users toward this
   repository for historical context.
5. Start a new unreleased section in `CHANGELOG.md`.

## Legacy Package

The old `zemit-cms/core` package should be treated as historical continuity for
existing users. Do not make it the primary install path in new documentation.
If it is marked abandoned later, point users to `phalcon-kit/core`.
