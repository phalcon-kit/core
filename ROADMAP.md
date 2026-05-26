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

Target: `2.4.x`

Theme: REST query hardening and request-surface safety.

Decision: keep `2.4.x` focused on REST query behavior. Promote non-REST work
only after these request-surface decisions are implemented or explicitly
deferred.

### REST Order Safety

Status: Next

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
- Keep framework-generated or application-set default ordering compatible.
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

Status: Design

Why:

- Some API clients need a count with list responses without calling a separate
  endpoint.
- `countAction()` already exists, and grouped count responses already have
  explicit opt-in response fields. The remaining question is whether
  `findAction()` should optionally embed count metadata when the frontend asks
  for it.

Scope:

- Define the frontend request parameter and response shape before
  implementation.
- Decide whether the count ignores pagination, honors filters/search/joins, and
  how it behaves with grouped queries.
- Require both a frontend request and a controller opt-in so count metadata is
  not returned by accident.
- Add tests for plain lists, filtered lists, grouped lists, and disabled count
  requests.

Compatibility:

- Do not change existing `findAction()` payloads unless the controller opts in
  or the request asks for an allowed count field.

Validation:

- REST action unit tests.
- Response contract documentation.

### Response Relationships On Demand

Status: Design

Why:

- Clients often need different relation graphs for list and detail views.
- Arbitrary request-driven eager loading can expose too much data, so it must
  be explicitly controlled.
- Dynamic joins already cover request-driven relationship joins for filtering
  and search. This item is only about choosing response eager-loading graphs at
  request time.

Scope:

- Define a request parameter for relation selection.
- Reuse the existing `with`/eager-loading policy surface where possible.
- Require an allow-list for client-requested relationships.
- Decide how defaults, request additions, and behavior-based removals merge.
- Add tests for allowed aliases, nested aliases, rejected aliases, list/detail
  actions, and behavior interaction.

Compatibility:

- Existing default eager-loading behavior must remain unchanged.
- Applications must be able to deny all request-driven relations.

Validation:

- Query state tests.
- REST `find` and `findFirst` action tests.
- Eager-loading regression tests when relation graphs are executed.

## Next Blocks

### Model Correctness

Status: Planned

Target: `2.5.x`

Scope:

- Audit snapshots should include only scalar mapped columns when assigned
  relations are present on a model.
- Relationship assignment strict mode should be designed before implementation.
- Model cache invalidation needs a key and whitelist strategy before changing
  the current coarse flush behavior.

Best first task:

- Start with audit snapshot filtering because it is narrow, easy to reproduce,
  and has clear regression-test value.

### Exception Taxonomy Cleanup

Status: Planned

Target: `2.5.x`

Scope:

- Replace generic public/framework exceptions with scoped PhalconKit exceptions
  where doing so improves developer experience.
- Keep native SPL exceptions where they are precise and internal.
- Clean stale broad `@throws` docblocks when they no longer describe real
  behavior.

Non-goal:

- Do not wrap every exception just to make it framework-scoped. The new
  exception must make the failure clearer or more stable for consumers.

### Runtime Assert Review

Status: Design

Target: `2.5.x`

Scope:

- Separate static-analysis narrowing asserts from runtime validation.
- Replace only public or runtime-critical assertions with explicit exceptions.
- Leave narrow local asserts in place when they are only used to help static
  analysis and the surrounding code already guarantees the type.

Validation:

- Search `assert(` before starting the block.
- Add tests only for assertions that become public runtime failures.

### Configurable Event Attachments

Status: Design

Target: Unscheduled

Scope:

- Decide whether configuration should attach listeners to database,
  dispatcher, models manager, logger, and other events-aware services.
- Define service-name, event-name, listener factory, and ordering rules.
- Ensure the design works for MVC, CLI, WebSocket, and test bootstraps.

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
- Dynamic model metadata and dynamic record model identity.
- Relationship assignment strictness and sparse payload behavior.
- Controller behavior response and permission merging.
- Additional `findIn*` helpers.
- Soft-delete event-state configuration.
- Eager-loading magic, option propagation, and limitation cleanup.
- Read/write replication listener idempotency.
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
