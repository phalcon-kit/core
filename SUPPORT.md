# Support

Use the right channel so issues stay actionable.

## Development And Releases

`master` carries unreleased Core 4.0 development. Published versions remain
available through their tags; there are no standing version branches. Use a
suitable tagged-version constraint and lockfile for applications. See the
[branch policy](guides/release.md#branch-policy) and
[Core 4.0 upgrade guide](guides/upgrading-4.0.md) before following `dev-master`.

## Questions

Use GitHub Discussions for usage questions, architecture questions, and general
help:

https://github.com/orgs/phalcon-kit/discussions

## Bugs

Open a GitHub issue when you can provide:

- PhalconKit version
- PHP version
- Phalcon version
- operating system
- database engine/version when relevant
- a minimal reproduction or failing test
- expected behavior and actual behavior

https://github.com/phalcon-kit/core/issues

## Security

Do not open public issues for vulnerabilities. Use GitHub private vulnerability
reporting from the repository Security tab.

See [SECURITY.md](SECURITY.md).

## Legacy Package

The previous Packagist package,
[`zemit-cms/core`](https://packagist.org/packages/zemit-cms/core), remains
available for existing applications and historical releases. New projects
should use [`phalcon-kit/core`](https://packagist.org/packages/phalcon-kit/core).
If you are maintaining an older project, keep your pinned constraint until you
are ready to test the package-name migration.
