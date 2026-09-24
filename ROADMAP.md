# Roadmap

This is the active release roadmap for Phalcon Kit Core. Completed changes
belong in [CHANGELOG.md](CHANGELOG.md), durable usage notes belong in the guides,
and unresolved API or migration decisions belong in
[To Be Discussed](guides/to-be-discussed.md).

## Current Focus

Target: `3.11.2` maintenance release on PHP 8.5 / Phalcon 5.22.

Keep the release focused on native behavior, enforced regression coverage, and
accurate consumer documentation. The implemented maintenance changes are listed
under the unreleased changelog; this file tracks the remaining work.

## Release Verification

Status: Next — target `3.11.2`.

- Run CI against the final release commit with both lowest and highest
  dependencies. Require the selected native database tests to execute without
  skips on the MySQL service, and exercise the worker-error callback with Swoole.
- Review the aggregate return-type compatibility note: minimum/maximum preserve
  native values, including integers, strings, and null.
- Verify a fresh Composer installation and the rebased IDE-stub patches using
  the [release process](guides/release.md) before tagging.

## Public Contract Documentation

Status: Next — small follow-up batches in the `3.11.x` maintenance line.

- Document relationship assignment input shapes, ownership checks, sparse
  payload behavior, transaction ownership, and eager-loading extension points.
- Document dynamic record/model metadata lifetime and required DI services.
- Continue through scaffold output helpers and TypeScript generation: array
  shapes, supported options, generated-file ownership, and overwrite behavior.
- Review remaining public/protected declarations for missing or misleading
  contracts. Inherit unchanged native contracts and keep simple accessors concise.

For each batch, compare comments with implementation and relevant tests. Validate
important examples against the public API, run focused checks for any behavior
changes, and leave generated API pages to an intentional documentation build.

## Analysis And Mechanical Hygiene

Status: Planned — after the current maintenance release is verified.

- Narrow mixed-value flows and broad signature suppressions at touched model,
  query, and scaffold boundaries using precise types and behavior tests.
- Correct existing `LICENSE.txt` headers in one separate mechanical change;
  the generation templates now point to the shipped `LICENSE` file.
- Prune disposable merged work branches after checking their tips; preserve
  historical release branches, signed commits, and tags.

Migration schema portability, REST controller scaffold ownership, and public API
removal remain design work in [To Be Discussed](guides/to-be-discussed.md).
Follow the [deprecation replacement table](guides/quality-and-maintenance.md#comment-and-deprecation-maintenance)
when updating consumers; do not remove public aliases in a maintenance release.
