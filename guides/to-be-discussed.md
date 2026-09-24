# To Be Discussed

This file tracks design questions that are worth revisiting, but should not
change public behavior until there is a concrete application need, migration
plan, and test coverage.

Actionable release blocks live in the [Project Roadmap](../ROADMAP.md). Promote
an item there only after the expected behavior, compatibility risk, and
validation plan are concrete.

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
  Reset-token delivery already has the protected application hook
  `sendPasswordResetNotification()`. Keep that extension contract. The remaining
  question is whether a reset-completed notification or an optional shared
  notifier is needed, and how delivery retries/failures should be exposed.
  Preserve anti-user-enumeration responses and never return reset tokens to clients.
- Impersonation authorization:
  `src/Identity/Traits/Impersonation.php`.
  Replace the hard-coded `admin`/`dev` role gate only after a config-backed
  impersonation permission contract exists, including audit/session behavior for
  "login as" flows.
- Identity role matching flag naming:
  `src/Identity/Traits/Role.php`,
  `src/Identity/Traits/Acl.php`.
  The legacy `$or` parameter name is confusing because current behavior treats
  `false` as any-match and `true` as all-match at the current nesting level.
  Documentation now states the real behavior, but a future API could add clearer
  names such as `hasAnyRole()`, `hasAllRoles()`, or an explicit match-mode
  value while keeping the existing methods for compatibility.
- Tag factory and legacy tag service split:
  `src/Provider/Assets/ServiceProvider.php`,
  `tests/Unit/Html/TagFactoryTest.php`.
  The assets manager needs a native-style `TagFactory`, while the public `tag`
  service exposes Phalcon Kit's static helper facade. A cleanup would likely need
  separate service names or a compatibility bridge.
- Database logger correlation context:
  `src/Db/Events/Logger.php`.
  Query logs currently include the effective user and impersonated user IDs.
  Decide whether they should also include session, request, trace, or
  correlation IDs. Any addition needs a stable service contract that works for
  MVC, CLI, WebSocket, and stateless JWT identity contexts.
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
  A historical inline note questioned `castOnHydrate` with binary columns, but
  provided no reproducer. Keep the current true default; require native binary
  and UUID round-trip evidence before proposing a change.
- Relationship assignment:
  `src/Mvc/Model/Traits/Relationship.php`,
  `tests/Unit/Mvc/Model/ModelTest.php`.
  Opt-in strict relation assignment now covers non-whitelisted aliases, unknown
  complex relation payloads, malformed relation values, and nested relation
  assignment. Remaining design work: a clearer replacement for boolean
  keep-missing sentinels, explicit rules for sparse payloads that reactivate
  or update existing relation rows, and future-major defaults for direct
  relationship ownership enforcement, unowned child adoption, and direct
  soft-delete auto-restore.
- Controller behavior responses and merge semantics:
  `src/Mvc/Controller/Traits/Behavior.php`.
  Define whether controller behaviors should collect multiple event responses
  and whether legacy feature/role permission merges should move from native
  recursive merging to the attribute resolver's de-duplicating merge helper.
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
- Eager-loading option propagation:
  `src/Mvc/Model/EagerLoading/Loader.php`,
  `src/Mvc/Model/EagerLoading/EagerLoad.php`.
  Loader options are captured but not passed into relation loading yet. Define
  explicit options for soft-delete visibility, intentional duplicate-row
  preservation, and per-relation constraints before changing the current load
  contract.
- Model cache invalidation:
  `src/Mvc/Model/Traits/Cache.php`.
  The current coarse flush predicate now invalidates on creates, deletes,
  restores, reorders, and changed snapshots while skipping unchanged snapshot
  saves. The minimum granular invalidation contract is documented in
  [Models And Eager Loading](models-and-eager-loading.md#future-granular-cache-policy).
  Keep targeted deletion parked until a real application needs key
  registration, reverse indexes, relation invalidation, and pre-warming beyond
  the shared `modelsCache->clear()` fallback.
- Lifecycle query ownership:
  `src/Mvc/Model/Traits/LifeCycle.php`.
  Moving lifecycle helpers into a model manager would be cleaner, but requires a
  migration plan for static CLI retention task calls.
- Dynamic join filtering:
  `src/Mvc/Controller/Traits/Query/DynamicJoins.php`,
  `src/Mvc/Controller/Traits/Query/Conditions/FilterSemantics.php`.
  Dynamic joins already support request-driven relationship joins for filters.
  Further filter hoisting and join-existence validation could improve
  performance or safety, but they need tests for aliases, permission-scoped
  joins, and user-supplied filter fields.
- REST query save initialization hooks:
  `src/Mvc/Controller/Traits/Query.php`,
  `src/Mvc/Controller/Traits/Query/Fields.php`,
  `src/Mvc/Controller/Traits/Query/Save.php`.
  Save-field initialization now runs through `initializeFields()`. Add dedicated
  `rest:before/afterInitializeSave` hooks only if applications need to mutate
  save-specific query state independently from filter/map/search/expose fields.
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
  `src/Mvc/Dispatcher/Module.php`, `src/Mvc/Dispatcher/Rest.php`,
  `src/Mvc/Dispatcher/Camelize.php`.
  Standardize module namespace rewriting, decide whether the pass-through REST
  dispatcher listener should gain supported behavior or be removed, and decide
  whether Camelize should be an opt-in listener, a default listener, or removed
  from the public dispatcher surface.
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
- TypeScript scaffold defaults:
  `src/Modules/Cli/Tasks/TsScaffoldTask.php`.
  Default values and related default objects are helper methods but are not
  emitted into generated TypeScript yet. Decide whether generated clients
  should preserve database defaults, expose DTO defaults, or leave defaults to
  server-side model validation.
- Scaffold output encoding:
  `src/Modules/Cli/Tasks/ScaffoldTask.php`.
  Generated PHP files are written as UTF-8 without a BOM. Add a BOM option only
  if a supported downstream editor or runtime requires it, because BOM output
  can affect headers and generated-file diffs.

## Optional Instagram Provider Dependency

- Status: Compatibility hold, reviewed for Core 3.10.8.
- Area: Optional OAuth2 Instagram integration and maintainer dependencies.
- Context: `league/oauth2-instagram` 3.1.0 depends on the abandoned
  `jakeasmith/http_build_url` package. The provider is installed for Core
  development coverage and suggested to applications, not required by the
  default production dependency graph. Upstream recommends PHP 8.5's native
  URI API, which is not a drop-in replacement for the provider's dependency.
- Current stance: Retain the optional integration for compatibility and use a
  package-specific Composer abandonment exception. Security advisories and
  abandonment checks for every other package remain enforced. Use Composer
  2.10 or newer for maintainer audits so `config.policy` is honored.
- Possible future shape: Upgrade to a maintained compatible provider or plan
  an explicit migration/removal of the integration. Avoid claiming a Composer
  replacement without implementing and testing the dependency's public API.
- Validation: Exercise authorization URL construction, callback state,
  token exchange, and resource-owner mapping with mocked HTTP responses;
  remove the exception and pass fresh lowest/highest dependency audits.
- Discussion triggers: An upstream provider release removes the dependency,
  a maintained alternative is selected, or a security advisory requires action.

## REST Controller Scaffold Ownership

Status: Design; resolve generated-file ownership before scheduling implementation.

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

## Ideas Requiring A Concrete Consumer Need

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

## Entry Template

- Status:
- Area:
- Context:
- Current stance:
- Possible future shape:
- Discussion triggers:
