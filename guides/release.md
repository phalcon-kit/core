# Release Process

Use this checklist when preparing a public release.

## Before Release

1. Confirm the target version and release branch.
2. Update `CHANGELOG.md` by moving the current unreleased section to a dated
   version heading.
3. Update runtime version metadata if needed.
4. Run the full local quality gate:

```shell
composer qa
```

5. Confirm Composer metadata:

```shell
composer validate --strict --no-check-publish
composer audit
```

6. Review public docs:
   - `README.md`
   - `CHANGELOG.md`
   - `SECURITY.md`
   - `SUPPORT.md`
   - `guides/`

## Tag

Use a SemVer tag:

```shell
git tag 1.1.0
git push origin 1.1.0
```

Prefer signed tags when possible.

## After Release

1. Verify the GitHub Actions workflow passed on the tag/default branch.
2. Verify Packagist updated `phalcon-kit/core`.
3. Check GitHub Code Scanning for fresh Psalm results.
4. Confirm the old `zemit-cms/core` page still points users toward this
   repository for historical context.
5. Start a new unreleased section in `CHANGELOG.md`.
