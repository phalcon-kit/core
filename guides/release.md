# Release Process

Use this checklist when preparing a public release.

## Version Policy

Phalcon Kit follows SemVer for tagged public releases. Keep unreleased work
under the current `Unreleased` heading in `CHANGELOG.md` until the tag is cut.

## Before Release

1. Confirm the target version and release branch.
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
3. Push the release commit to the release branch.
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

The old `zemit-cms/core` package should be treated as historical continuity for
existing users. Do not make it the primary install path in new documentation.
If it is marked abandoned later, point users to `phalcon-kit/core`.
