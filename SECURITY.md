# Security Policy

## Supported Versions

Only the **4.x line** is maintained. All earlier versions are end of life and
receive no support, bug fixes, security fixes, or backports. This includes
historical releases published as `zemit-cms/core`.

| Version line | Maintained | Notes |
| --- | --- | --- |
| 4.x | Yes | Supported stable line; use the latest tagged 4.x release. |
| 0.x, 1.x, 2.x, 3.x | No | End of life; no fixes or backports. |

Older tags remain available for historical use and reproducible
installs. See the [Core 4.0 upgrade guide](guides/upgrading-4.0.md) for migration
requirements and application acceptance checks.

## Reporting a Vulnerability

Do not report security vulnerabilities through public GitHub issues.

Use GitHub private vulnerability reporting from the repository Security tab, or
open a private repository security advisory if you are a maintainer. Include:

- affected versions and environment details;
- reproduction steps or proof-of-concept code;
- expected impact and severity;
- any known mitigations or workarounds.

We aim to acknowledge valid reports promptly, coordinate fixes privately when
needed, and publish an advisory or release notes when disclosure is appropriate.

## Security Practices

The default project checks are:

- Composer validation and dependency audit;
- PHPCS coding-standard checks;
- PHPStan static analysis;
- Psalm static analysis;
- Psalm taint analysis;
- PHPUnit regression tests;
- GitHub Actions workflow scanning with zizmor;
- OpenSSF Scorecard supply-chain checks.

Additional tools such as Qodana or SonarCloud may be used by maintainers, but
they are not treated as required gates unless a workflow for them exists in this
repository.

## Developer Guidelines

Before submitting a pull request, run:

```bash
composer qa
```

Security-sensitive changes should include focused tests where practical,
especially around input validation, query compilation, identity/permission
checks, serialization, escaping, cryptography, file handling, and generated
model/scaffolding behavior.

## References

- [GitHub private vulnerability reporting](https://docs.github.com/en/code-security/security-advisories/working-with-repository-security-advisories/configuring-private-vulnerability-reporting-for-a-repository)
- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/index.html)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://phptherightway.com/#security)
- [PHPStan Documentation](https://phpstan.org/)
- [Psalm Documentation](https://psalm.dev/)
- [OpenSSF Scorecard](https://github.com/ossf/scorecard-action)
- [zizmor](https://zizmor.sh/)
