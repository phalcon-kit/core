# Release Process

Use this checklist when preparing a public release.

## Version Policy

Phalcon Kit follows SemVer for tagged public releases. Keep unreleased work
under the current `Unreleased` heading in `CHANGELOG.md` until the tag is cut.

## Branch Policy

`master` is the sole long-lived branch and currently contains unreleased Core
4.0 development. Version branches are not kept as release archives; existing
tags preserve published releases. Short-lived contribution branches may
be removed once their work is merged or otherwise preserved.

Publish releases with signed version tags. Applications should use tagged-version
constraints and keep their lockfiles; `dev-master` intentionally follows current
development and can introduce breaking changes during a major-version transition.

Only Core 4.x is maintained, as defined in [SECURITY.md](../SECURITY.md).
All earlier versions are end of life: do not prepare maintenance releases or
security backports for them. Preserve their existing tags as historical records.
Core 4.0 is still unreleased, so there is currently no supported stable release.

## Before Release

1. Confirm the target 4.x version and commit on `master`.
2. Update `CHANGELOG.md` by moving the current unreleased section to a dated
   version heading.
3. Update `src/Support/Version.php` and its focused version test to the exact
   release number.
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
8. When public or protected APIs changed, regenerate the API reference with
   `composer docs`, synchronize the generated API and guides into the Docs
   repository, and build the complete MkDocs site.
9. Check package contents:

```shell
composer archive --format=tar --dir=/tmp
```

Review the archive if package exclusions changed.

## Commit And CI

1. Review `git status --short`, the complete diff, and `git diff --check`.
2. Create and verify a signed release commit containing only the reviewed
   release changes.
3. Push the release commit to `master`.
4. Wait for every required workflow on that exact commit SHA. Do not tag while
   a required check is pending or failing.

## Tag And GitHub Release

Only after the exact release commit passes CI, create and verify a signed tag:

```shell
printf 'Release version: '
read -r VERSION
git tag -s "$VERSION" -m "Release $VERSION"
git verify-tag "$VERSION"
git push origin "$VERSION"
```

Create the GitHub Release from the verified tag with curated notes and the
correct previous-tag comparison link.

## After Release

1. Verify the GitHub Actions workflow passed on the tag and that the GitHub
   Release targets the expected commit SHA.
2. Verify Packagist updated `phalcon-kit/core` and resolves the exact tag SHA.
3. Publish the synchronized Docs commit and verify its GitHub Pages workflow.
4. Check GitHub Code Scanning for fresh Psalm results.
5. Confirm the old `zemit-cms/core` page still points users toward this
   repository for historical context.
6. Start a new unreleased section in `CHANGELOG.md` in the next development
   commit.

## Legacy Package

The old `zemit-cms/core` package is unmaintained and unsupported. Preserve it as
historical continuity for existing users; direct new documentation to Core 4.x.
If it is marked abandoned later, point users to `phalcon-kit/core`.
