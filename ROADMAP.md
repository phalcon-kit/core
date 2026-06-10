# Roadmap

This file is the active release roadmap for Phalcon Kit Core. It replaced the
retired GitHub Project board, but it should not become an archive of finished
work.

Use this file for deliverables that are concrete enough to schedule and test.
Keep design questions in [To Be Discussed](guides/to-be-discussed.md), keep
completed public changes in [CHANGELOG.md](CHANGELOG.md), and keep durable usage
guidance in the relevant guide or shipped skill reference.

## How To Use This File

- Keep entries scoped to a deliverable that can be implemented, tested, and
  released.
- Promote a design question only when the problem, compatibility risk, expected
  behavior, and validation plan are clear.
- Prefer opt-in behavior for new framework capabilities unless a breaking
  release explicitly changes the default.
- Keep `Current Focus` short enough that the next block is obvious.
- Remove completed blocks after the changelog and user-facing docs capture the
  result. Do not leave historical `Done` sections here.
- Keep cancelled or speculative ideas out of the roadmap unless they prevent a
  likely future misstep; otherwise track them in To Be Discussed.

## Status Values

- `Next`: ready to implement in the current release train.
- `Planned`: valuable, but should wait until the current focus is stable.
- `Design`: needs an API contract, migration plan, or application use case.
- `Parking Lot`: valid idea, but not worth scheduling yet.

## Current Focus

Target: `3.0.4` release readiness, then `3.1.x` planning

Theme: keep the completed `3.0.4` coverage pass releasable, then start the
next development block with a clear scaffold ownership contract.

Decision:

- The `3.0.4` coverage batch is complete and belongs in the changelog, not in
  active roadmap blocks.
- Do not add broad runtime behavior to `3.0.4`; keep remaining work limited to
  QA fixes, release notes, and release mechanics.
- After `3.0.4`, the next development block is REST controller scaffold
  readiness. Start with generated-file ownership before adding public
  scaffolding behavior.

Release principles:

- Add focused regression tests before changing framework defaults.
- Keep no-database harnesses available for REST policy and model trait behavior.
- Run full QA before any tag, and document skipped integration prerequisites
  when optional services are unavailable.
- Public API additions need PHPDoc, guide updates, changelog entries, and tests
  that show the compatibility path.

## Next Blocks

### 3.0.4 Release Readiness

Status: Next

Target: `3.0.4`

Why:

- The testing coverage pass is complete, but the release should not ship with a
  failing database-backed unit or stale planning docs.
- The release should preserve the current public behavior and avoid mixing
  scaffolding feature work into the patch train.

Scope:

- Keep `composer qa:test`, style, package skeleton, and practical static checks
  green for the release environment.
- Keep the changelog focused on shipped behavior and maintainer workflow
  changes.
- Remove completed testing-priority inventories from active planning docs.
- Cut and publish the release only after the local release gate is clean.

Validation:

- `composer qa:test`.
- `composer phpcs`.
- `composer skeleton`.
- `composer phpstan`.
- `git diff --check`.

### REST Controller Scaffold Readiness

Status: Planned

Target: `3.1.x`

Why:

- Scaffolding REST controllers can save application work, but it can also
  freeze bad defaults or overwrite application-owned decisions if started too
  early.
- The REST controller contracts are much more stable after recent policy work,
  but generated output still needs a precise ownership
  model.

Scope:

- Inventory the stable controller extension points: permissions, filters,
  search fields, save fields, order fields, distinct fields, response fields,
  `with` graphs, transformers, and action enablement.
- Define which files are generated once, which files are regenerated, and which
  files are app-owned.
- Decide whether scaffolded controllers are abstract bases, concrete shells, or
  an opt-in pair similar to model abstract/concrete scaffolding.
- Add scaffold tests in temporary directories before generating any real
  application-facing controller output.
- Align generated controller comments with the existing model/scaffolder
  documentation style.

Validation:

- Scaffold output assertions against temporary directories.
- No hand edits to generated API documentation.
- Full QA before release because scaffolding changes can affect package
  consumers even when runtime code is untouched.

## Design Backlog

These areas remain in [To Be Discussed](guides/to-be-discussed.md) until there
is a concrete application need and a compatible API shape:

- Identity password reset notifications.
- Impersonation authorization.
- Identity role matching naming.
- Tag factory and legacy tag service split.
- Database logger correlation context.
- CLI router interface compatibility.
- Model setup defaults.
- Model cache key registration, reverse indexes, relation invalidation, and
  pre-warming.
- Dynamic model metadata and dynamic record model identity.
- Relationship sparse payload behavior.
- Controller behavior response and permission merging.
- Additional `findIn*` helpers.
- Soft-delete event-state configuration.
- Eager-loading magic, option propagation, and limitation cleanup.
- Lifecycle query ownership.
- Dynamic join optimization and filter hoisting.
- REST save initialization hooks.
- Aggregate `WHERE`/`HAVING` promotion.
- Faker task table scope and seed modes.
- Binary UUID fetch-time conversion.
- Locale `__isset()` and `__unset()` semantics.
- Dispatcher listener public surface.
- Translation keys containing delimiters inside nested arrays.
- Environment loader invalid-type strictness.
- ClamAV positive scan fixture strategy.
- TypeScript scaffold defaults.
- Scaffold output encoding.

## Parking Lot

### JetBrains Attributes

Status: Parking Lot

Scope:

- Add `#[Deprecated]`, `#[Pure]`, or other JetBrains attributes only where they
  materially improve IDE feedback.
- Do not add vendor-specific attributes broadly until there is a clear policy
  for dependency and PHPDoc compatibility.

### CMS Models And Controllers

Status: Parking Lot

Scope:

- Do not start this without a product-level CMS contract.
- If revived, split it into separate model, permission, REST controller,
  migration, and documentation blocks.

### OpenAPI Generation

Status: Parking Lot

Scope:

- Do not revive the old controller-introspection idea. REST policies can be
  dynamic, identity-aware, and action-specific.
- Revisit only as an explicit resource metadata contract after REST request and
  response contracts stabilize and a real consumer needs generated OpenAPI
  output.

### Dynamic Expose Property Creation

Status: Parking Lot

Scope:

- Do not automatically create undefined expose properties.
- Revisit only if an application has a concrete, safe use case that cannot be
  solved with explicit exposer configuration.
