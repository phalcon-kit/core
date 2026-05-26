# Roadmap

This file is the repository-owned replacement for the retired GitHub Project
board. It tracks actionable release work for Phalcon Kit Core.

Use this file for work that is concrete enough to schedule. Keep open design
questions in [To Be Discussed](guides/to-be-discussed.md), and keep completed
public changes in [CHANGELOG.md](CHANGELOG.md).

## How To Use This File

- Keep roadmap entries scoped to a deliverable that can be tested and released.
- Promote a design question only when the problem, compatibility risk, and
  validation plan are clear.
- Prefer opt-in behavior for new framework capabilities unless a breaking
  release explicitly changes the default.
- Update the target version when a block moves between release trains.
- Move completed public work to the changelog when the release is prepared.

## Status Values

- `Next`: ready to implement in the current release train.
- `Planned`: valuable, but should wait until the current focus is stable.
- `Design`: needs an API contract, migration plan, or application use case.
- `Parking Lot`: valid idea, but not worth scheduling yet.
- `Done`: shipped or otherwise fully handled.
- `Cancelled`: intentionally not planned.

## Current Focus

Target: `2.6.x`

Theme: Low-risk framework diagnostics and runtime assertion cleanup.

Decision: REST request-surface work shipped in `2.4.x` and `2.5.x`. The next
line should favor narrow correctness fixes, clear failure modes, and concrete
regression tests over new broad API surface.

### REST Order Safety

Status: Done in the current `2.4.x` development line

Why:

- REST controllers already expose client-controlled ordering.
- Filters, search, expose, save, distinct, and response fields have explicit
  policy surfaces; ordering should have the same framework-level clarity.
- This is a small, testable hardening block before more dynamic request
  features are added.

Scope:

- Add an allowed-order-fields policy, initializer, abstract contract, and
  getter/setter pair that match the existing controller query patterns.
- Preserve current behavior for controllers that do not opt in to restricted
  ordering.
- Reject unauthorized client-supplied order fields with a clear HTTP exception.
- Keep framework-generated or application-set default ordering compatible by
  compiling defaults through the same parser and requiring allow-listed fields
  only when a controller explicitly configured an order policy.
- Allow applications to reuse filter fields as their order policy when that is
  what they want, but do not make filter fields an automatic fallback in the
  `2.x` line. A field can be safe to filter without being safe or efficient to
  sort, and automatic fallback would silently tighten existing controllers that
  already define filter fields.
- Add unit coverage for string order syntax, array order syntax, default order,
  empty order input, allowed fields, and rejected fields.
- Update REST documentation and changelog notes when behavior changes.

Compatibility:

- Existing controllers must keep working unless they opt in to an allowed-order
  policy.
- The policy must support model-qualified and relationship-qualified order
  fields consistently with filter/search field naming.

Validation:

- Targeted query/order tests.
- `composer phpunit`.
- Full QA before release tagging.

### Embedded REST List Counts

Status: Done in the current `2.4.x` development line

Why:

- Some API clients need a count with list responses without calling a separate
  endpoint.
- `countAction()` already exists, and grouped count responses already have
  explicit opt-in response fields. The remaining question is whether
  `findAction()` should optionally embed count metadata when the frontend asks
  for it.

Scope:

- Define `count` as the frontend request parameter for list-count metadata.
- Support `count`, `groupedCount`, `bucketTotal`, and `totalCount` response
  fields using the same semantics as `countAction()`.
- Count metadata honors filters/search/joins/permissions and ignores
  pagination through the shared count query helper. `totalCount` removes group
  clauses like the count action extra field.
- Require a frontend request so count metadata is not returned by accident.
  Controllers can optionally restrict or block embedded counts; a null policy
  stays unrestricted across supported framework count fields.
- Add tests for plain lists, filtered lists, grouped lists, eager-loaded list
  actions, rejected fields, and disabled count requests.

Compatibility:

- Do not change existing `findAction()` payloads unless the request asks for a
  supported count field. Controllers that need a closed policy can pass an
  empty collection to block every embedded count field.

Validation:

- REST action unit tests.
- Response contract documentation.

### Response Relationships On Demand

Status: Done in the current `2.5.x` development line

Why:

- Clients often need different relation graphs for list and detail views.
- Arbitrary request-driven eager loading can expose too much data, so it must
  be explicitly controlled.
- Dynamic joins already cover request-driven relationship joins for filtering
  and search. This item is only about choosing response eager-loading graphs at
  request time.

Scope:

- Define `with` as the request parameter for response relation selection.
- Keep `findAction()` relation-free; only `findWithAction()` and
  `findFirstWithAction()` read the request-time relation parameter.
- Reuse the existing controller `with` collection as both the default eager-load
  graph and the allow-list for request-time subsets.
- Treat a missing `with` request parameter as "use the configured defaults" and
  a present parameter as "load only this allowed subset."
- Support direct nested requests such as `Author.Profile.Avatar`; the eager
  loader builds required parent paths internally, and configured parent
  constraints are preserved when the requested child path needs them.
- Reject requested child paths outside the configured graph instead of letting
  arbitrary relationship aliases reach the eager loader.
- Add tests for default behavior, no-relationship `findAction()`, allowed
  aliases, nested aliases, parent-of-configured nested aliases, rejected aliases,
  and `findFirstWithAction()`.

Compatibility:

- Existing default eager-loading behavior is unchanged when the request does
  not include `with`.
- Applications can deny all request-driven relations by leaving `with` null or
  setting an empty collection.

Validation:

- Query state tests.
- REST `find` and `findFirst` action tests.
- Eager-loading regression tests when relation graphs are executed.

## Next Blocks

### Model Correctness

Status: Done in the current `2.6.x` development line

Target: `2.6.x`

Scope:

- Audit snapshots should include only scalar mapped columns when assigned
  relations are present on a model. Done in the current `2.6.x` development
  line.
- Relationship assignment strict mode should be designed before implementation.
  Done in the current `2.6.x` development line as an opt-in per-model guard.
- Model cache invalidation needs a key and whitelist strategy before replacing
  the current coarse flush behavior. The coarse flush predicate itself was fixed
  in the current `2.6.x` development line so creates, deletes, restores,
  reorders, and changed snapshots invalidate cache while unchanged snapshot
  saves do not. The future granular cache policy contract is documented in
  [Models And Eager Loading](guides/models-and-eager-loading.md#future-granular-cache-policy).
- Read/write replication listener attachment now tracks the model events
  manager that received the callbacks, so repeated replication initialization
  does not duplicate write-event listeners.

Follow-up:

- Keep targeted cache invalidation in the design backlog until key
  registration, reverse indexes, relation invalidation, and pre-warming
  semantics are backed by a real application use case.

### Exception Taxonomy Cleanup

Status: Done in the current `2.6.x` development line

Target: `2.6.x`

Scope:

- Replace generic public/framework exceptions with scoped PhalconKit exceptions
  where doing so improves developer experience.
- Keep native SPL exceptions where they are precise and internal.
- Clean stale broad `@throws` docblocks when they no longer describe real
  behavior.
- REST CSV exports now wrap invalid League CSV options in `HttpException` and
  writer failures in scoped runtime exceptions while preserving the original
  vendor exception as `previous`.
- Native Phalcon MVC/bootstrap failures are documented as intentionally
  propagated unchanged so application HTTP/domain exceptions keep their original
  type and status semantics.

Validation:

- Runtime source now has no native generic `throw new \Exception`,
  `\RuntimeException`, `\LogicException`, or `\InvalidArgumentException` sites,
  and no stale broad `@throws \Exception` annotations.

Non-goal:

- Do not wrap every exception just to make it framework-scoped. The new
  exception must make the failure clearer or more stable for consumers.

### Runtime Assert Review

Status: Done

Target: `2.6.x`

Scope:

- Separate static-analysis narrowing asserts from runtime validation.
- Replace only public or runtime-critical assertions with explicit exceptions.
- Leave narrow local asserts in place when they are only used to help static
  analysis and the surrounding code already guarantees the type.

Validation:

- Search `assert(` before starting the block.
- Add tests only for assertions that become public runtime failures.

Outcome:

- Converted runtime-sensitive model behavior, metadata helper, controller model
  lookup, string formatting, and eager-loading assertions into contextual
  PhalconKit exceptions.
- Added focused unit coverage for behavior registry, behavior option, metadata
  host, controller loader, and eager-loading contract failures.
- Left dense relationship, eager-loading internals, and CLI scaffolder asserts
  in place where reviewed code uses them as local static-analysis narrowing
  after Phalcon or reflection APIs already constrain the value shape.

### Configurable Event Attachments

Status: Done in the current `2.6.x` development line

Target: `2.6.x`

Scope:

- Added opt-in `eventsManager.listeners` configuration for the shared Phalcon
  events manager used by database, dispatcher, model, view, and application
  services.
- Listener definitions support class names, DI service names, explicit
  priorities, constructor arguments, and `enabled => false` for merged config.
- Bootstrap attaches configured listeners after providers are registered and
  before modules/router setup, with idempotency for repeated `bootServices()`
  calls in tests or custom bootstraps.

Non-goal:

- Do not introduce per-service event-manager replacement in `2.6.x`. Existing
  providers already share the main manager, and replacing service-owned managers
  needs a separate compatibility plan.

### REST API Controller Scaffolding

Status: Planned

Target: Unscheduled

Scope:

- Generate concrete or abstract REST API controller layers only after the
  controller contract is stable.
- Include permission, filter, search, save, order, with, response-field, and
  transformer placeholders without overwriting application-owned code.
- Align with the database scaffolder and TypeScript scaffold defaults.

### Testing Architecture

Status: Planned

Target: Ongoing

Scope:

- Continue adding regression tests with each bug fix.
- Use [Testing Roadmap](guides/testing-roadmap.md) for unit, component,
  integration, database, and REST-flow test tiers.
- Keep test additions tied to behavior risk rather than chasing coverage
  percentage alone.

## Design Backlog

These areas remain in [To Be Discussed](guides/to-be-discussed.md) until there
is a concrete application need and a compatible API shape.

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
- Relationship assignment strictness and sparse payload behavior.
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

Status: Cancelled

Reason:

- The old OpenAPI card never had a working implementation in this repository.
- Do not revive it as controller introspection. REST policies can be dynamic,
  identity-aware, and action-specific, so generated OpenAPI should come from an
  explicit resource metadata contract if it is ever reintroduced.
- Revisit only after REST request/response contracts stabilize and there is a
  clear consumer for generated OpenAPI output.

### Dynamic Expose Property Creation

Status: Cancelled

Reason:

- Automatically creating undefined expose properties was cancelled on the old
  project board.
- Revisit only if an application has a concrete, safe use case that cannot be
  solved with explicit exposer configuration.

## GitHub Project 5 Migration Snapshot

Reviewed: 2026-05-26

This snapshot preserves the retired GitHub Project 5 cards so closing the
project does not lose planning context.

### Active Cards Migrated

- RESTful API System: Add new allowed order fields definitions.
- RESTful API System: Allow request-time count during `find`.
- RESTful API System: Allow request-time relationships during `find` and
  `findFirst`.
- Refactor thrown exceptions into clearer contextual PhalconKit exceptions.
- Add JetBrains attributes where they provide real IDE value.
- Allow configuration-backed event listener attachment.
- Add complete scaffolding for RESTful API controllers.
- Create more unit tests and clarify unit, functional, integration, and
  end-to-end test boundaries.
- Review runtime `assert()` usage and replace public/runtime validations with
  clearer exceptions where appropriate.
- Create CMS models and controllers.

### Cards Already Handled

- Improve PHP documentation parameter typing and returns.
- Update license stamps everywhere.
- Add typed class constants.
- Switch support models and model maps to use DI.
- Add interfaces for REST and RESTful controllers.
- Change static helper classes to use services where possible.
- Create permission configs for models and controllers.
- Refactor the identity service into smaller components.
- Allow REST filters to specify `and`, `or`, and `xor` without extra nesting.
- Clarify stateless identity behavior. Broad stateless session work is not
  planned because applications can choose the noop session service when needed.
- Re-integrate multi-entry save behavior.
- Integrate the dynamic join system.
- Clarify that request-time relationship joins for filtering already belong to
  dynamic joins; only request-time response eager-loading remains a design
  question.
- Review inline TODO and commented code markers, with remaining design value
  captured in [To Be Discussed](guides/to-be-discussed.md).

### Cards Closed Without Planned Work

- Generate OpenAPI config from RESTful controllers.
- Improve expose behavior by creating undefined properties on the fly.
