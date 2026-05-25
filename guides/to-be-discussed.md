# To Be Discussed

This file tracks design questions that are worth revisiting, but should not
change public behavior until there is a concrete application need, migration
plan, and test coverage.

## REST Grouped Count Totals

Status: Open

Area: REST controllers, aggregate query responses

Context:

- `count()` can return either a scalar total or a grouped count result when a
  `group` clause is present.
- A grouped count result is useful for bucket counts, but callers sometimes
  also want a total count in the same response.
- Summing grouped buckets is not always the same as counting unique root
  records, especially when grouping by joined relations where one root record
  can appear in multiple buckets.

Current stance:

- Do not change `count()` to return a mixed structured payload such as
  `['groups' => ..., 'total' => ...]`; that would break callers and blur native
  Phalcon return semantics.
- Do not automatically sum grouped counts and label that value as a total.
- If this is added later, prefer explicit names and opt-in behavior.

Possible future shape:

- `groupedCount`: grouped count result.
- `totalCount`: separate ungrouped count query using the same filters.
- `bucketTotal`: sum of grouped buckets, explicitly named so it is not confused
  with a unique root-record total.

Discussion triggers:

- A real application needs both grouped bucket counts and total counts in one
  response.
- Desired behavior is clear for joined-group cases.
- The performance cost of an extra count query is acceptable or configurable.

## Stateless Session Option

Status: Open

Area: session provider, identity, API-only applications

Context:

- Some REST/API applications authenticate with JWTs, bearer tokens, signed
  requests, or another stateless mechanism and do not want PHP session cookies,
  session files, or server-side session state.
- The current session provider always builds a Phalcon session manager and
  starts it, defaulting to the stream adapter when no adapter is configured.
- Phalcon's `Noop` adapter is useful, but a true stateless mode may also need
  to skip `start()`, avoid `Set-Cookie` headers, and define how session reads
  and writes behave.
- Existing consumers can rely on sessions for identity fallback, locale
  persistence, flash messages, OAuth2 state, CSRF/security behavior, session
  bags, and impersonation flows.

Current stance:

- Do not silently make `Noop` the default or disable sessions globally; that
  would break existing session-backed applications.
- Do not treat "stateless" as only a storage-adapter choice until cookie,
  startup, and session-dependent service behavior are defined.
- If added later, it should be an explicit opt-in configuration with clear
  migration notes for services that require session state.

Possible future shape:

- `session.stateless: true` or `session.enabled: false` as an explicit config
  flag.
- A no-state session registration strategy, such as a `Noop` manager that does
  not start, a null session service compatible with the expected interface, or
  a clear exception when session-dependent code is used.
- Recommended stateless API config, for example JWT identity without
  `identity.sessionFallback`, locale mode without session persistence, and an
  OAuth2 state strategy that does not depend on PHP sessions.
- Tests proving no session files are written, no session cookie is emitted, and
  known session consumers fail clearly or use documented fallbacks.

Discussion triggers:

- A real API application needs to run without server-side session state.
- Desired behavior is clear for OAuth2 state, flash messages, locale,
  session bags, impersonation, and security/CSRF helpers.
- Security review accepts the logout, replay, token revocation, and state
  validation tradeoffs for the stateless path.

## Reviewed Inline Follow-Ups

Status: Reviewed; selected items remain open.

Area: framework internals, public API design, test coverage

Context:

- On 2026-05-23, every inline follow-up marker outside `vendor/` and generated
  API docs was reviewed.
- Vague source/test comments were replaced with current-behavior comments or
  removed when they were stale.
- The items below are the ones that still have real design value. They should
  not change public behavior until the desired API shape, migration risk, and
  test coverage are clear.

Current stance:

- Keep source comments focused on known limitations and current behavior; do
  not use inline comments as a second backlog.
- Do not implement these as drive-by fixes. Most touch public framework
  contracts or long-standing compatibility behavior.
- Prefer opt-in behavior, compatibility notes, and regression tests before
  changing existing defaults.

Keep for discussion:

- Identity password reset notifications:
  `src/Identity/Manager.php`.
  Decide whether reset-token and reset-completed messages belong in events, a
  notifier service, or app-owned callbacks. Any design must preserve the current
  anti-user-enumeration response behavior.
- Impersonation authorization:
  `src/Identity/Traits/Impersonation.php`.
  Replace the hard-coded `admin`/`dev` role gate only after a config-backed
  impersonation permission contract exists, including audit/session behavior for
  "login as" flows.
- Tag factory and legacy tag service split:
  `src/Provider/Assets/ServiceProvider.php`,
  `tests/Unit/Html/TagFactoryTest.php`.
  The assets manager needs a native-style `TagFactory`, while the public `tag`
  service exposes PhalconKit's static helper facade. A cleanup would likely need
  separate service names or a compatibility bridge.
- CLI router interface compatibility:
  `src/Cli/Router.php`, `tests/Unit/Cli/RouterTest.php`,
  `tests/Unit/Cli/ModuleTest.php`, `tests/Unit/Ws/ModuleTest.php`.
  `Phalcon\Cli\RouterInterface` exists in the current runtime, but
  `Phalcon\Cli\Router` is still not an instance of it because native return
  signatures such as `setDefaultAction()` and `getRouteById()` do not match the
  interface. Keep framework code typed against `PhalconKit\Router\RouterInterface`
  unless upstream Phalcon changes the native implementation.
- Model setup defaults:
  `src/Mvc/Model.php`.
  Revisit `notNullValidations => false` only after generated-model validation,
  database-nullability assumptions, and application migration risk are tested.
- Dynamic model metadata:
  `src/Mvc/Model/Dynamic.php`.
  Replace APCu metadata key deletion with a metadata strategy or adapter wrapper
  only if it handles dynamic sources without changing normal model caching.
- Dynamic record model identity:
  `src/Modules/Api/Controllers/RecordController.php`.
  The controller now uses `Dynamic::createInstance()` instead of runtime
  `eval()`-generated subclasses. Revisit only if a real app needs distinct
  model class names per dynamic source for metadata, events, or policy hooks.
- Relationship assignment:
  `src/Mvc/Model/Traits/Relationship.php`,
  `tests/Unit/Mvc/Model/ModelTest.php`.
  Consider an opt-in strict mode for unknown aliases, a clearer replacement for
  boolean keep-missing sentinels, and explicit rules for sparse payloads that
  reactivate or update existing relation rows.
- Controller behavior merging:
  `src/Mvc/Controller/Traits/Behavior.php`.
  Define whether controller behaviors should collect multiple event responses
  and how feature/role permission merges should de-duplicate overlapping
  entries.
- `findIn*` model helpers:
  `src/Mvc/Model/Traits/FindIn.php`.
  Expand beyond `findInById()` only after field validation, bind-type
  inference, and method naming rules are defined.
- Soft delete event state:
  `src/Mvc/Model/Traits/SoftDelete.php`.
  Decide whether model setup options should expose ORM event state instead of
  reading the native INI flag inside the trait.
- Static eager-loading magic:
  `src/Mvc/Model/Traits/EagerLoad.php`.
  Keep `__callStatic()` for compatibility unless moving to Phalcon
  `missingMethods()` is proven to intercept the same public method names.
- Model cache invalidation:
  `src/Mvc/Model/Traits/Cache.php`.
  Design cache keys, whitelist rules, and pre-warming before replacing the
  current coarse flush behavior.
- Read/write replication listeners:
  `src/Mvc/Model/Traits/Replication.php`.
  Decide whether repeated initialization needs an idempotency guard or a
  behavior object before changing listener attachment.
- Lifecycle query ownership:
  `src/Mvc/Model/Traits/LifeCycle.php`.
  Moving lifecycle helpers into a model manager would be cleaner, but requires a
  migration plan for static CLI retention task calls.
- Dynamic join filtering:
  `src/Mvc/Controller/Traits/Query/DynamicJoins.php`,
  `src/Mvc/Controller/Traits/Query/Conditions/FilterSemantics.php`.
  Filter hoisting and join-existence validation could improve performance or
  safety, but they need tests for aliases, permission-scoped joins, and
  user-supplied filter fields.
- Aggregate WHERE/HAVING promotion:
  `src/Mvc/Controller/Traits/Query.php`.
  Automatically moving conditions containing aggregate functions from `where`
  to `having` is disabled. A future implementation needs parser-aware behavior
  or strict tests so normal fields containing function-like text are not moved
  incorrectly.
- Scaffolded API controller generation:
  `src/Modules/Cli/Tasks/ScaffoldTask.php`.
  Controller generation was previously sketched but is not active. Decide
  whether scaffolding should own concrete API controllers, or whether generated
  model abstracts/interfaces should remain the only core-owned scaffold output.
- Faker task table scope:
  `src/Modules/Cli/Tasks/FakerTask.php`.
  The current task generates data for the first non-deleted table. Generating
  all dynamic tables needs explicit limits, table filtering, and safety rules
  before it can be enabled.
- Distinct REST action:
  `src/Mvc/Controller/Traits/Actions/Rest/DistinctAction.php`.
  Implement only after response shape, allowed fields, joins, permissions,
  pagination, and transformer behavior are specified.
- Audit snapshots:
  `src/Mvc/Model/Behavior/Blameable.php`.
  Ensure snapshots include only scalar mapped columns even when assigned
  relations are present on the model.
- Eager loading limitations:
  `src/Mvc/Model/EagerLoading/Loader.php`,
  `src/Mvc/Model/EagerLoading/EagerLoad.php`.
  Composite relation keys, multiple local/reference fields, through-relation
  soft-delete visibility, optional grouping, and loader options need a coherent
  API instead of isolated condition edits.
- Binary UUID lifecycle:
  `src/Mvc/Model/Traits/Uuid.php`.
  Binary UUID create support exists, but fetch-time conversion and native
  database UUID round-tripping need a tested API before adding transform hooks
  for existing rows.
- Locale model magic:
  `src/Mvc/Model/Interfaces/LocaleInterface.php`.
  Add `__isset()`/`__unset()` only after translated-property presence semantics
  are defined.
- Dispatcher listeners:
  `src/Mvc/Dispatcher/Module.php`, `src/Mvc/Dispatcher/Rest.php`.
  Standardize module namespace rewriting and decide whether the pass-through
  REST dispatcher listener should gain supported behavior or be removed.
- Translation keys containing the delimiter inside nested arrays:
  `tests/Unit/Translate/TranslateTest.php`,
  `src/Translate/Adapter/NestedNativeArray.php`.
  Supporting this would require a longest-match lookup strategy that preserves
  the existing flat-key precedence.
- Environment loader invalid types:
  `tests/Unit/Support/EnvTest.php`, `src/Support/Env.php`.
  Current behavior normalizes invalid types to `Mutable`; stricter exceptions
  would be a behavior change and should be explicit.
- ClamAV positive scan fixture:
  `tests/Unit/Provider/ClamavTest.php`.
  Add EICAR coverage only with a CI-safe fixture/download strategy that does
  not trigger repository, package, or local antivirus scanners unexpectedly.

Closed or clarified during review:

- Blank comments in flash, exposer, and dispatcher code were removed or
  replaced with explicit current-behavior comments.
- Commented translation coverage was restored with the real `Phalcon Kit`
  message key; delimiter-containing nested translation keys remain an open
  design question above.
- Commented ClamAV file-positive coverage was removed because stream-based
  EICAR coverage already asserts positive detection without storing an EICAR
  fixture in the repository.
- Commented debug dumps, obsolete fallback throws, and runtime `eval()` sketch
  code were removed from source. Current behavior is now either executable code
  or tracked in this discussion guide.
- REST query initialization now carries the configured aggregate `column`
  collection into prepared find options instead of leaving the live code
  commented out.
- JSON escaping of `null` remains `null` as a string because the helper is used
  for `JSON.parse(decodeURIComponent(...))` payloads.
- Event cancellation behavior is now asserted directly instead of questioned in
  a comment.
- The disabled multibyte sprintf encoding test was removed because the example
  used Chinese text with an encoding that cannot represent it.

## Entry Template

- Status:
- Area:
- Context:
- Current stance:
- Possible future shape:
- Discussion triggers:
