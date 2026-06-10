# Testing Strategy

This guide describes how to grow package coverage without blocking application
work. It is not the active release backlog. Use [ROADMAP.md](../ROADMAP.md) for
schedulable work, and use [CHANGELOG.md](../CHANGELOG.md) for completed
coverage batches.

The original `3.0.4` P0 through P4 testing pass is complete. Its results are
recorded in the changelog, so this guide now keeps only durable testing
strategy: tiers, phases, and the working pattern for future batches.

The phases below are a coverage model, not a live completion board. Track active
release work in the roadmap and move completed outcomes to the changelog.

## Goals

- Cover behavior before implementation details.
- Keep each test file close to the runtime namespace it protects.
- Prefer simple fakes and harness classes until the behavior genuinely needs a
  database, dispatcher, or request cycle.
- Add regression tests next to every bug fix once the failure shape is known.
- Use fixtures only where a literal input/output example is clearer than a
  builder.

## Test Tiers

Use these tiers to decide how much infrastructure a test may use.

| Tier | Scope | Allowed Dependencies | Examples |
| --- | --- | --- | --- |
| Pure unit | One function, helper, trait, value object, or formatter | None beyond PHP and PHPUnit | string helpers, array helpers, options, collection policies |
| Component unit | One class with framework-facing collaborators | Hand-built fakes or DI services already present in `AbstractUnit` | filters, validators, serializers, exposers, behavior classes |
| Contract test | One public extension contract or scaffolded convention | Minimal fake implementation of the contract | provider registration, generated interface expectations, transformer fallback |
| Integration test | Multiple framework services together | Test DI, test config, in-memory or disposable services | router/dispatcher, request parsing, permission condition compilation |
| Database integration | Models and query behavior | Test database and migrations | model lifecycle hooks, uniqueness, soft delete, relationship queries |
| REST flow | Route through response payload | Test HTTP kernel, identity fixtures, database | list/get/save/delete/restore, filters, pagination, permissions |

## Phase 0: Inventory And Baseline

Purpose: know what is already protected before adding larger tests.

- Keep a namespace-by-namespace coverage map under the open issue or milestone.
- Record skipped tests and why they are skipped.
- Note test prerequisites that are not obvious, such as required PHP extensions
  or optional services.
- Keep `composer phpunit` green before starting each new phase.

Useful targets:

- `src/Functions`
- `src/Support`
- `src/Filter`
- `src/Events`
- `src/Http`
- `src/Mvc/Controller/Behavior`
- `src/Mvc/Model/Behavior`
- `src/Provider`
- `src/Modules`

## Phase 1: Pure Support Units

Purpose: finish the low-hanging, deterministic surface first.

Add or tighten tests for:

- `src/Functions`: array helpers, printf helpers, dump guards.
- `src/Support/Helper`: string and array helpers with edge cases.
- `src/Support/Options`: default, merge, reset, and null-value semantics.
- `src/Support/CollectionPolicy`: nested merge/replace/remove behavior.
- `src/Support/Exposer`: key parsing, builder state, callback processing.
- `src/Http/StatusCode`: known code phrases and unknown-code fallback.
- `src/Filter`: sanitizer and validator wrapper behavior.

Exit criteria:

- Every pure helper has direct tests, not only aggregate tests through
  `Support\Helper`.
- Edge cases are named in test method names.
- Focused runs and full `composer phpunit` pass.

## Phase 2: Behavior And Query Primitives

Purpose: test event-driven controller/model behavior in isolation before using
real controllers or models.

Add tests for:

- Query condition add/remove behavior classes.
- Skip/remove behavior aliases and backward-compatible wrappers.
- Permission condition collection mutations.
- Dynamic join configuration validation.
- Filter, group, order, column, and bind normalization helpers.
- Lifecycle event hooks that are easy to trigger with a harness object.

Exit criteria:

- Behavior classes can be trusted independently from REST actions.
- Invalid configuration tests cover the exception messages that app developers
  will see.

## Phase 3: Providers, Config, And DI Glue

Purpose: protect the service wiring that apps depend on.

Add tests for:

- Provider registration idempotence.
- Provider aliases and shared service behavior.
- Config merge, append, replacement, and environment override semantics.
- Model namespace and model map resolution.
- Open extension points where apps replace framework defaults.

Exit criteria:

- Providers can be registered twice without hidden side effects when expected.
- Config tests show how replacement differs from append/merge.

## Phase 4: HTTP, Dispatcher, Router, And CLI

Purpose: cover request-cycle primitives without entering model-heavy flows.

Add tests for:

- Router defaults and route normalization.
- Dispatcher module/controller/action resolution.
- Request parameter access and filter application.
- CLI router/task parsing.
- Module namespace registration.
- WebSocket route and dispatcher setup where it can be tested without a live
  socket server.

Exit criteria:

- HTTP and CLI routing behavior is described by tests, not just examples.
- Test fixtures avoid hard-coded app-specific namespaces.

## Phase 5: REST Controller Units Without Database

Purpose: cover controller policy initialization and response shapes before
testing real persistence.

Add harness-based tests for:

- `initializeSaveFields()`, `initializeSearchFields()`, and
  `initializeFilterFields()` collection behavior.
- `initializeWith()`, `initializeJoins()`, and `initializeDynamicJoins()`.
- `initializePermissionConditions()` mutations.
- `count`, `get`, `get-all`, `save`, `delete`, and `restore` response view
  shapes using fake models or stubs.
- Optional compatibility shims if they are ever promoted into a small,
  opt-in core namespace.

Exit criteria:

- Common REST policy methods can be tested without scaffolding a database table.
- Response shape tests separate core behavior from any application-specific
  naming.

## Phase 6: Models Without Relationships

Purpose: cover concrete model invariants that do not need relationship queries.

Add tests for:

- Generated abstract/interface expectations.
- Default property values.
- Typed constants and domain constants.
- Validation rules that do not require a database lookup.
- Transformable behavior input/output rules.
- Lifecycle hooks that can run against a standalone entity.

Exit criteria:

- Concrete models can stay lean because scaffolded behavior is covered by core
  tests.
- App examples can override only domain-specific behavior.

## Phase 7: Database-Backed Models

Purpose: cover persistence, metadata, and behaviors that require a real model
manager and database.

Add tests for:

- Create/update/delete/restore lifecycle behavior.
- Soft delete and restore scopes.
- Blameable/audit behavior.
- Uniqueness validation.
- UUID/default value handling.
- Column maps and metadata-driven save fields.

Exit criteria:

- Database setup is repeatable locally and in CI.
- Tests use minimal schemas and fixtures, not production-like dumps.

## Phase 8: Relationships And Eager Loading

Purpose: cover the relationship layer after model persistence is stable.

Add tests for:

- `belongsTo`, `hasOne`, `hasMany`, and `hasManyToMany` aliases.
- Generated `addDefaultRelationships()` behavior.
- Soft-delete-aware relationship queries.
- `findWith()` and nested eager-loading graphs.
- Callback-driven eager-loading order and filters.
- Parent relation assignment and null handling.

Exit criteria:

- Relationship tests distinguish Phalcon native behavior from PhalconKit
  eager-loading behavior.
- Nested graphs are covered with compact, named fixtures such as `Foo`,
  `FooBar`, `User`, `Role`, `UserRole`, and `Group`.

## Phase 9: REST API Integration

Purpose: verify the public REST contract end to end.

Add tests for:

- Route-to-action dispatch.
- Identity and role checks.
- Permission conditions and condition-remover behavior.
- Search, filters, advanced filters, sorting, grouping, pagination, and count.
- Save payload assignment, nested save fields, validation failures, and
  relationship sync.
- Delete/restore response shapes.

Exit criteria:

- Each REST action has success and failure coverage.
- Error payloads include the status codes and messages app developers rely on.

## Phase 10: Scaffolding, CLI, And Generated Output

Purpose: protect code generation and package tooling after runtime behavior is
well covered.

Add tests for:

- Scaffolding output in temporary directories.
- Generated abstracts, interfaces, concrete models, controllers, permissions,
  and TypeScript output.
- CLI task arguments and failure paths.
- Skeleton validation output.

Exit criteria:

- Generated files are compared through stable snapshots or targeted assertions.
- Tests avoid rewriting committed generated documentation.

## Phase 11: Hardening And Coverage Policy

Purpose: make coverage durable without slowing down everyday work too much.

Add:

- A coverage report job once the baseline is useful.
- Namespace-level minimums for pure units before global thresholds.
- A flaky-test quarantine process with issue links.
- Mutation testing only after the suite is fast and stable enough.
- Release checklist reminders for new public APIs.

Exit criteria:

- CI tells maintainers what broke and where to add tests next.
- Coverage thresholds ratchet upward by namespace instead of blocking useful
  incremental work.

## Working Pattern For Each Batch

1. Pick one namespace and one tier.
2. Add focused tests for public behavior and edge cases.
3. Run the focused PHPUnit command for the new files.
4. Run `composer phpunit`.
5. Run `composer phpcs` when PHP files changed.
6. Run `git diff --check`.
7. Update `CHANGELOG.md` only when the batch changes public behavior, docs,
   tooling, or maintainer workflow.
