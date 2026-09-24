# Support

Use the right channel so issues stay actionable.

## Development And Releases

Only Core **4.x** is maintained and supported. All earlier versions are end of
life, with no support, bug fixes, security fixes, or backports.

`master` carries unreleased Core 4.0 development, so there is currently no
supported stable release. Published versions remain available through their
tags for historical use and reproducible installs; there are no standing version
branches. Keep existing application lockfiles while preparing an isolated
migration. See the [branch policy](guides/release.md#branch-policy) and
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
[`zemit-cms/core`](https://packagist.org/packages/zemit-cms/core), is unmaintained
and unsupported. Its releases remain available for historical use. Migration to
the maintained line requires both the [package-name migration](guides/migration-from-zemit.md)
and the Core 4.0 upgrade checks; renaming the dependency alone is insufficient.
