# Roadmap

This is the active release roadmap for **Core 4.0 on `master`**.
Implemented changes belong in [CHANGELOG.md](CHANGELOG.md); consumer guidance
and the removal inventory live in [Upgrading To Core 4.0](guides/upgrading-4.0.md).
Published releases remain available through tags. The repository keeps a single
long-lived branch; see the [release policy](guides/release.md#branch-policy).

## Existing Application Schema Acceptance

Status: Required before a stable 4.0 release.

- Exercise application-owned upgrade migrations against isolated existing schemas.
  The new fresh baseline deliberately refuses existing tables and history.
- Review actual consumer engine/collation/index differences and data compatibility
  before converting them. Preserve application migration history and retained data.

The fresh baseline and reusable SQL migration pattern are documented in
[Database Migrations](guides/database-migrations.md). Its shipped validation
belongs in the changelog; existing application acceptance remains separate.

## Retained Feature Contracts

Status: Next — focused batches with consumer fixtures.

- Document required models/tables/services for identity, permissions, templates,
  email, files, audit, and settings.
- Review hard-coded model lookups and generated-interface coupling. Keep the
  existing model resolver; correct specific gaps with focused behavior tests.
- Document relationship payloads, ownership checks, transactions, and eager
  loading, then scaffold output ownership and TypeScript generation.
- Review the remaining prepared models only after service/relationship closure
  and consumer need are understood. Do not expand the removal list by name alone.

## Consumer And Distribution Acceptance

Status: Required before tagging 4.0.

- Exercise isolated application upgrades: login/registration/reset/session
  behavior, permission and tenant filters, REST response contracts, nested
  writes/eager loading, file/audit behavior, and CLI/WebSocket extension points.
- Preserve REST aliases until SDK and wrapper callers have an explicit migration.
- Pass lowest/highest dependency CI, required native database regressions,
  Swoole callback coverage, and a fresh Composer installation with stub patches.
- Coordinate Core and App **4.0.0** tags. After Core is published, replace App's
  temporary `^4.0@dev` constraint with `^4.0`, lock the stable Core tag, pass
  App CI, and verify a fresh public project install before tagging App.
- Regenerate API docs from the settled public surface and verify guide examples.
- Publish the upgrade guide with the 4.x-only support policy in `SECURITY.md`.
  Migrate existing `dev-master` consumers deliberately; that constraint now
  follows 4.0 development, while their existing lockfiles retain the old commit.

Broader integration packaging, framework upgrades, mass renaming, and
license-header cleanup remain separate from the 4.0 scope.
