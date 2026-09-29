# Contributing to Phalcon Kit

Phalcon Kit is an open source project and a volunteer effort. Contributions are
welcome when they are reproducible, focused, and aligned with the framework
direction.

## Contributions

Contributions to Phalcon Kit should be made in the form of GitHub pull requests.
Each pull request will be reviewed by a core contributor and either merged or
given feedback for the changes required before it can be merged. All
contributions should follow this format, including contributions from core
contributors.

## Questions & Support

GitHub issues are for reproducible bugs, accepted feature requests, and pull
requests. Use [SUPPORT.md](SUPPORT.md) to choose the right support channel.

Before opening an issue, check:

- [README.md](README.md)
- [guides/](guides/)
- [CHANGELOG.md](CHANGELOG.md)
- [GitHub Discussions](https://github.com/orgs/phalcon-kit/discussions)

If you still believe you found a bug, open an issue with a minimal
reproduction.

## Bug Report Checklist

- Do not report security vulnerabilities in public issues. Use GitHub private
  vulnerability reporting from the repository Security tab instead.
- Use the latest released version of Phalcon Kit before submitting a bug report.
- Include the smallest code, schema, configuration, or repository that
  reproduces the issue.
- Include Phalcon Kit version, Phalcon version, PHP version, operating system,
  and database version when relevant.

## Pull Request Checklist

- Branch from the target branch and keep pull requests focused.
- Do not include unrelated formatting, generated artifacts, or submodule
  updates.
- Add focused tests for behavior changes when practical.
- Run `composer qa` before opening the pull request.
- Update `CHANGELOG.md` under the current unreleased version section when a pull
  request changes public behavior, compatibility, security posture, tooling, or
  maintainer workflow.
- Update documentation or skills when changing public behavior, generated
  output, or maintainer workflow.

## Writing Application Documentation

Core's `guides/` directory is the canonical source for narrative guides. Write
for developers building their own applications with PhalconKit:

- Lead READMEs with what users can build, installation, a first request, and task links.
- Describe current behavior. Keep release history in the changelog/tagged history
  and support policy in its dedicated file; do not lead setup guides with it.
- Keep migration instructions under `guides/migrations/`. Use its shared scope,
  preparation, changes, verification, rollback, and related-guide structure; link
  current recipes instead of maintaining separate versions of their examples.
- Give prerequisites, exact file locations, required permission/configuration,
  copyable commands, and expected responses or persisted results.
- Distinguish complete examples from snippets and application-owned services
  from Core capabilities. Verify examples through the actual runtime when their
  behavior depends on routing, serialization, ORM, or authentication.
- Use neutral synthetic schemas and data. Never copy private project names,
  customer identifiers, domains, credentials, or proprietary business rules.
- Keep Markdown readable on GitHub and the documentation site. Link related
  topics instead of maintaining conflicting copies of a tutorial.
- Synchronize narrative guides into `phalcon-kit/docs`, update its navigation,
  check local links/anchors, and build the site. Keep the App README aligned.
- Keep generated class/function reference files separate from narrative edits.
  Run `composer docs` only when intentionally regenerating that reference.

For prose-only changes, `git diff --check` and targeted link/example checks are
normally sufficient; runtime changes still need the relevant Composer checks.

## Requesting Features

If you have a change or new feature in mind, open a feature request on GitHub.

### New Feature Request (NFR)

A new feature request should explain the problem, the proposed behavior, and how
it can be implemented without weakening framework performance or compatibility.

An NFR contains:
- Suggested syntax
- Suggested class names and methods
- Short documentation
- Comparison with similar features in other frameworks when useful

In the following cases a new feature request will be rejected:
- The feature slows down common paths without a clear benefit.
- The feature does not provide additional framework value.
- The request is unclear or lacks enough documentation to evaluate.
- The request does not match the current framework direction.
- The request breaks current applications without a migration path.
- The request cannot be implemented safely.
- The request only helps development/testing scenarios.
- Proposed classes/components do not follow the [Single Responsibility
  Principle][SRP].

Feature requests do not need a complete implementation. They should give enough
context to discuss whether the change belongs in the core package.

Feature requests should be posted as new issues on [GitHub][github-issues].

[SRP]: https://en.wikipedia.org/wiki/Single-responsibility_principle
[github-issues]: https://github.com/phalcon-kit/core/issues
