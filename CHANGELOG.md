# Changelog

All notable changes to Phalcon Kit Core are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html)
for tagged public releases.

Add upcoming work under the current unreleased version section. When cutting a
release, replace the unreleased marker with the release date and keep compare
links in release notes or GitHub releases in sync.

Historical entries before 2026-05-20 were reconstructed from Git tags, commit
history, the old changelog, and committed file changes. Older Zemit-era entries
are summarized where the commit history is too granular to be useful as
release notes.

## 1.1.x - Unreleased

The current source tree reports package runtime version `1.1.0`, but no `1.1.0`
tag has been cut yet.

### Added

- Added PHP 8.5 as the package target and CI runtime.
- Added Phalcon 5.13.x dependency constraints, documentation references, and
  patch updates.
- Added Composer QA/security scripts:
  - `composer qa` for the full local quality gate.
  - `composer qa:composer` for Composer validation and audit.
  - `composer qa:style` for PHPCS.
  - `composer qa:static` for PHPStan and Psalm.
  - `composer qa:security` for Psalm taint analysis.
  - `composer qa:test` for PHPUnit.
  - `composer psalm:taint` for direct taint-analysis runs.
- Added script descriptions for the QA commands so `composer list` explains the
  maintainer workflow.
- Added configurable wrappers for Psalm, PHPStan, and PHPCS so local and CI runs
  share the same entry points, thread controls, memory limits, and cache paths.
- Added debug mode support to the PHPStan wrapper.
- Added GitHub issue templates, a pull request template, grouped Dependabot
  updates, private vulnerability reporting guidance, OpenSSF Scorecard, and
  zizmor workflow scanning.
- Added a manual Code Scanning triage workflow that summarizes open alerts by
  tool, category, and rule, can upload an empty SARIF replacement for one exact
  stale category, and can delete old analyses for one exact category.
- Added project-level `AGENTS.md`, `AI.md`, `.aiignore`, and public repo hygiene
  guidance.
- Added reusable PhalconKit AI skill bundles for app development and core
  maintenance under `resources/skills/`.
- Added detailed app-developer references for Phalcon baseline usage, bootstrap,
  configuration, providers, filters, validation, REST controllers, transformers,
  model mapping, scaffolding, relationships, behaviors, eager loading, identity,
  security, CLI, websocket, logging, support helpers, OpenAI runtime, testing,
  and integrations.
- Added official Phalcon documentation references to the skill set wherever
  PhalconKit extends native Phalcon features.
- Added Swoole analysis stubs so static analysis can run without requiring the
  Swoole extension to be loaded locally.
- Added Phalcon 5.13 scaffolding documentation for schema, UUID, and generated
  model updates.
- Added application config flags to `.env.testing`.
- Added this reconstructed changelog and maintainer workflow reminders to keep
  future release notes current.
- Added a simplified public README, focused project guides, support
  documentation, and updated Composer support links for docs/security metadata.
- Added public guides for architecture, models/eager loading,
  identity/permissions, and quality/maintenance.
- Added a guide index with a recommended reading order and official Phalcon
  documentation entry points.
- Added an end-to-end resource walkthrough showing schema, scaffolding,
  concrete model logic, REST controller policies, transformer output, and
  permission config.
- Added concrete provider, eager-loading, transformer-backed action, and
  advanced filter examples to the public guides.
- Reframed the public README and guides around user tasks, faster Phalcon REST
  API development, and schema-to-resource how-to workflows.
- Added a first REST resource tutorial with request/response examples, a legacy
  resource-walkthrough redirect, README FAQ, docs badge, and
  `zemit-cms/core` migration guide.
- Added more targeted Composer package metadata for REST APIs, scaffolding,
  eager loading, identity, permissions, CLI, and WebSocket use cases.
- Linked the public README and guide index more explicitly to the bundled AI
  skill documentation and added AI/agent guidance to the documentation issue
  template.
- Added a 0.x RESTful resource migration guide covering Phalcon DevTools model
  generation to PhalconKit scaffolding, getter-based REST policies to
  `initialize*()` policy collections, keyed joins, permission conditions,
  eager-loading graphs, custom actions, and route/action mapping.
- Expanded the RESTful migration guide with a temporary legacy bridge strategy
  for old frontend action names, old getter methods, lifecycle-event adapters,
  old `single`/`list` response keys, and staged controller migration.
- Added app API base-controller migration guidance for moving shared
  permission helpers, eager-loading helpers, transformer fallback, and
  compatibility initialization from old Zemit controllers to PhalconKit
  controllers.
- Added concrete model migration guidance covering DevTools-era models,
  generated abstracts/interfaces, default relationship/validation setup,
  app-specific alias overrides, transform behaviors, typed constants, and
  domain lifecycle hooks.
- Added application config migration guidance for moving root config imports,
  module constants, model mappings, provider overrides, permission fragments,
  task permissions, integration settings, and secrets from Zemit-era config to
  PhalconKit-era config.
- Added permission-fragment migration guidance covering unified `components`,
  condition-remover behavior classes, workflow-specific features, legacy action
  names, direct model method permissions, and renamed domain models.
- Expanded RESTful migration guidance with status/node model invariants,
  metadata-derived nested save fields, and computed order aliases.
- Expanded RESTful migration guidance with loader path normalization,
  application/devtools bootstrap migration, app module base-class migration,
  and simple-resource controller examples.
- Expanded RESTful migration guidance with the actual 0.4.46 to dev-master
  code gap, the improved event-driven behavior system, and the lean concrete
  model pattern enabled by scaffolded abstracts/interfaces.
- Clarified that legacy REST bridge traits shown in migration examples are
  app-owned compatibility code unless promoted into a small, opt-in, tested
  core compatibility namespace.
- Added a phased testing roadmap for growing coverage from pure support units
  through behavior, provider, REST, model, relationship, eager-loading, and
  scaffolding tests.
- Added direct low-level unit coverage for array helper, options, exposer,
  filter sanitizer, validator, slug, and utility behavior.
- Added direct unit coverage for custom helper factory registration and the
  static helper facade.
- Added broader low-level unit coverage for options managers, filter factory
  wiring, PHP runtime helpers, status-code aliases, debug assets, array/string
  formatting helpers, JSON escaping, translation adapters, and exception
  subclasses.
- Added additional low-level coverage for raw Fractal serialization,
  transformer/manager contracts, and locale lookup helpers.
- Added component-level coverage for event firing and injectable DI service
  caching.
- Added unit coverage for environment casting/defaults, config append merges,
  provider base contracts, and common support provider registrations.
- Added low-level coverage for database column constants, logger manager
  adapters, URL normalization, security random UUID helpers, and request
  origin/preflight edge cases.
- Added framework-glue coverage for HTTP responses, MVC module routes, MVC
  module service wiring, collection policy edge cases, config-backed model
  maps, and common provider services.
- Added low-level coverage for bootstrap provider/module error paths, CLI
  argument parsing, CLI/WebSocket constructors, MVC application/view wrappers,
  and CLI exception/router serialization helpers.
- Added service-provider unit coverage for core DI registrations, common
  framework services, configured logger/filter/security services, and locale
  session/http priority edge cases.
- Added a fallback path for the PHPUnit coverage wrapper so
  `composer test:coverage` works with Xdebug even when the optional
  `phpunit-coverage` helper is not installed.
- Added more unit coverage for provider registration paths, JWT helper
  round-trips, and REST query state initialization/merge edge cases.
- Added focused branch coverage for sprintf helpers, logger adapters, slug
  normalization, injectable property fallback, event-manager guards, and query
  field presence helpers.

### Changed

- Reworked the README around PhalconKit's current framework role: Phalcon 5.13
  baseline, generated models, scaffolding, eager loading, REST controllers,
  identity, permissions, transformers, websocket/CLI usage, providers, filters,
  logging, helpers, and AI integration.
- Moved long-form setup, configuration, scaffolding, REST, web server, and
  release-process details out of the README and into `guides/`.
- Expanded the existing getting-started, configuration, scaffolding, REST,
  web-server/WebSocket, and release guides with practical commands, ownership
  boundaries, extension points, and decision rules.
- Clarified that `zemit-cms/core` is the historical Packagist package while
  `phalcon-kit/core` is the package name for new installations.
- Updated contributor guidance to point at the current local docs, support
  policy, changelog workflow, and QA gate.
- Updated package metadata, Composer constraints, CI runtime, and development
  dependencies for PHP 8.5 and Phalcon 5.13.
- Upgraded GitHub Actions to current compatible versions, including
  `ramsey/composer-install` v4 where available.
- Updated Phalcon model and interface patches for the Phalcon 5.13 return-type
  surface, including restored `ResultsetInterface` return declarations.
- Updated `find()` and `findInById()` related annotations and return types to
  align with the restored Phalcon `ResultsetInterface` contract without adding
  unnecessary runtime traversability checks.
- Updated `Url::get()` compatibility for the Phalcon 5.13 `replaceArgs`
  signature.
- Updated `EnvTest`, `ConfigTest`, database handling, and `.env` loading logic.
- Updated TypeScript scaffolding support for Phalcon 5.13 schema and column type
  changes, including `Column::TYPE_TINYINTEGER`.
- Hardened the REST query compiler, dynamic joins, filter semantics, permission
  conditions, identity conditions, group/order handling, and save payload
  validation.
- Improved alias-aware filtering and existential condition support in the REST
  query compiler.
- Improved field normalization for bracketed fields, related-field aliases,
  grouped filters, and flattened group/order values.
- Improved `DynamicJoins` with stricter alias validation, safer condition
  generation, better dynamic join type resolution, and null-safe behavior.
- Improved `SaveAction` and query save payload validation for create/update
  flows.
- Improved the Exposer/Builder APIs with stricter state handling and mutability
  contracts.
- Expanded Blameable audit behavior with configurable toggles, safer JSON
  handling, and audit trail improvements.
- Simplified eager-loading parent relation assignment and null handling.
- Updated OpenAI service provider setup to use the newer factory configuration
  path and default config values.
- Standardized path separator handling for cross-platform URL/path behavior.
- Updated composer archive exclusions so local QA files, generated artifacts,
  tests, stubs, and AI guidance files do not ship in release archives.

### Fixed

- Fixed multibyte string precision formatting in `mb_vsprintf()`.
- Fixed logger formatter `date`/`dateFormat` handling during logger loading.
- Standardized logger configuration on `dateFormat` and `LOGGER_*_DATE_FORMAT`
  env names.
- Fixed Psalm, PHPStan, PHPCS, and PHPUnit failures caused by PHP 8.5, Phalcon
  5.13, and stricter local/CI analyzer settings.
- Fixed GitHub workflow drift where CI and local analyzer commands reported
  different Psalm results.
- Fixed stale GitHub Code Scanning results by restoring Psalm SARIF upload from
  the PHP security workflow with a stable category and error-only report output.
- Fixed Code Scanning triage guardrails so empty SARIF cleanup refuses
  non-matching tool/category pairs instead of creating unrelated empty analyses.
- Added bulk Code Scanning cleanup for every analysis category currently
  producing open alerts, with dry-run output before deletion.
- Made Code Scanning analysis deletion idempotent so already-deleted analyses
  from GitHub's eventually consistent API do not fail cleanup runs.
- Fixed noisy OpenSSF Scorecard Code Scanning output by keeping Scorecard
  published as JSON/API results instead of repository alerts.
- Fixed Composer audit handling so unlocked or lock-file-less installs do not
  fail the wrong workflow path.
- Fixed Node.js action deprecation exposure by moving workflow actions toward
  current Node 24-compatible versions where available.
- Fixed MySQL adapter exception context encoding and PDO attribute handling for
  newer MySQL driver versions.
- Fixed `executePrepared()` exception formatting and context details in the
  MySQL adapter.
- Fixed Profiler override declarations and nanosecond/second elapsed-time math
  under strict static analysis.
- Fixed false/null handling in filter condition compilation, filter semantics,
  dynamic joins, group handling, model parameter parsing, and eager-loading
  relation assignment.
- Fixed invalid `preg_split()`, `preg_replace()`, `strrpos()`, `substr()`, and
  JSON encoding result assumptions surfaced by stricter Psalm checks.
- Fixed Blameable JSON normalization to avoid returning `false` from methods
  declared as `string`.
- Fixed Blameable deleted callbacks to handle null and empty values.
- Fixed validation `allowEmpty` handling for multiple empty values.
- Fixed `Env::load()` so names are only set when appropriate.
- Fixed relationship save handling after upstream Phalcon changes around dirty
  related models.
- Fixed local static-analysis behavior when Swoole classes are referenced but
  the extension is not installed in the analyzer runtime.
- Fixed redundant null coalescing and key exposure logic in query/order paths.
- Fixed stale Psalm issue suppressions after the 5.13/PHP 8.5 cleanup.

### Security

- Added private vulnerability reporting guidance and public security policy
  improvements.
- Added security-focused QA entry points for Composer audit, Psalm taint
  analysis, OpenSSF Scorecard, and workflow static analysis.
- Restored Psalm SARIF publishing to GitHub Code Scanning for the current PHP
  8.5 security workflow without publishing informational Psalm notes as alerts.
- Kept Scorecard and zizmor out of GitHub Code Scanning so the Security tab
  stays focused on actionable code-analysis alerts.
- Configured zizmor to require action refs without requiring every GitHub Action
  to be pinned to a commit hash.
- Added Dependabot cooldowns and replaced the Redis CI service `latest` tag
  with an explicit version tag.
- Tightened public repository hygiene around ignored AI/tooling files and
  generated/local artifacts.

### Removed

- Removed stale Codeception configuration from the active test path.
- Removed unused analyzer configuration, exclusions, suppressions, and local
  artifacts that no longer matched the PHP 8.5/Phalcon 5.13 workflow.

## 1.0.1 - 2025-11-08

### Added

- Added request coverage for `Request::isPost()`.

### Changed

- Rebranded package metadata and documentation to `phalcon-kit`.
- Updated package docs, badges, and README references after the package rename.
- Enhanced parameter handling with typed signatures, filtering, caching, and
  helper methods.
- Improved query method signatures for more flexible return types.
- Updated Composer patch handling to `cweagans/composer-patches` 2.x.
- Updated Phalcon model/interface stub patches for more accurate return types.
- Updated CI/static-analysis workflow handling, including Psalm invocation and
  CodeQL v4.
- Updated Dependabot configuration for Composer, GitHub Actions, and optional
  NPM dependency paths.

### Fixed

- Fixed relationship delete checks so they only apply to valid resultsets.
- Fixed query primary-key resolution with `uuid` support and nullable default
  group handling.
- Fixed model interface/stub patches for more accurate Phalcon model return
  types.
- Fixed Psalm workflow output handling after SARIF upload experiments.
- Removed false-positive Psalm suppressions for undefined Swoole classes in the
  1.0.x workflow path.

### Removed

- Removed unused SARIF result cleanup and upload plumbing from the Psalm
  workflow path.

## 1.0.0 - 2025-10-28

### Added

- Added the first `phalcon-kit/core` 1.x release line after the Zemit-era
  package history.
- Added `declare(strict_types=1)` across PHP files.
- Added PHP 8.4 compatibility work and Phalcon 5.8/5.9-era support updates.
- Added UUIDv7 support in the security/random component and moved default model
  UUID generation toward application-layer UUIDv7.
- Added improved model scaffolding for generated abstracts/interfaces, typed
  getters/setters, model mappings, enum handling, indexes/references, and
  relationship metadata.
- Added generated abstract model interfaces so applications can focus custom
  models on business logic while scaffolding maps the database schema.
- Added generated model tests for scaffolded models.
- Added expanded model and controller query traits for filters, search,
  permission conditions, soft-delete conditions, group/order/limit/offset,
  joins, dynamic joins, expose/save fields, and REST actions.
- Added separate REST action traits for find, find-first, aggregate, save,
  restore, reorder, new, and index flows.
- Added response cache configuration options and richer REST response debug
  context.
- Added cache-header and Vary-header helpers in the REST response path.
- Added filter allow-list helpers and nested filter/search key flattening.
- Added `RemoveDefaultSoftDeleteConditionWhileFiltering`,
  `RemoveSoftDeleteConditionsWhileFiltering`, and filter-field presence helpers
  for permission/soft-delete behavior control.
- Added eager-loading improvements, including composite-key work and extensible
  query-builder paths.
- Added model relationship improvements for single-to-many, many-to-many,
  has-many-through style access, reusable records, and related-record save
  behavior.
- Added model instance support through `Instance` traits and interfaces.
- Added behavior removal support in model behavior traits and managers.
- Added explicit typings to many model behavior, Blameable, SoftDelete,
  Relationship, EagerLoad, Options, Cache, Query, Save, Dispatcher, Config, and
  helper APIs.
- Added websocket module/task classes and Swoole service-provider support.
- Added OpenAI, Redis, Swoole, WebSocket, database read-only/dynamic, and logger
  provider refinements.
- Added debug page assets, logger/loggers support, richer exception handling,
  and service-provider coverage.
- Added shell scripts for missing/enforced `declare(strict_types=1)` detection
  and insertion.
- Added local and CI tooling for PHPUnit, Psalm, PHPStan, PHPCS, phpDocumentor,
  Qodana, license stamping, skeleton validation, and Composer scripts.
- Added run configuration files for common project tools.
- Added broad unit test coverage for helpers, filters, security, locale,
  request/response, CLI/MVC bootstrap, models, providers, options, exposers,
  routers, URLs, and generated model behavior.

### Changed

- Reorganized package binaries, documentation generation scripts, patches, and
  Composer metadata for the PhalconKit package identity.
- Reworked package command names from `zemit` toward `phalcon-kit`.
- Reworked REST controller query initialization and response handling.
- Reworked generated model abstracts and migration resources around renamed
  fields such as `uuid`, `key`, and `label`.
- Reworked identity, JWT, impersonation, OAuth2, roles, ACL, and permissions
  internals with stricter typing.
- Reworked `Crypt` and security provider configuration for clearer cipher,
  signing, and AEAD compatibility rules.
- Reworked model event and instance traits to improve relationship state and
  related-record behavior.
- Reworked cache, export, params, relationships, events, behavior management,
  model mappings, and helper APIs for stronger type information.
- Reworked `RestResponse` around response caching, debug context, and future
  responder/domain separation.
- Reworked `NestedNativeArray`, `Exposer`, `Sprintf`, slug generation,
  UTF-8 sanitization, and JSON depth helpers.
- Reworked database replication timestamp logic and added millisecond helpers.
- Reworked debug HTML/CSS/JS assets and removed outdated frontend/admin view
  templates from the package core.
- Reworked Docker/dev examples and later removed unused Docker and web template
  assets from the 1.x tree.
- Moved documentation tooling from older phpDocumentor invocation patterns to
  the current `phpdoc` wrapper.
- Updated Composer dependencies and optional dependency suggestions.
- Updated CI caching for PHP setup and Composer dependencies.
- Lowered and tuned Psalm configuration during the large type-safety migration.

### Fixed

- Fixed REST search/filter behavior, average/sum/min/max query actions, offset
  handling, and cache-key generation.
- Fixed scaffolding output paths, eager relationship properties, enum constants,
  and generated model tests.
- Fixed database event logger/session recursion, duplicated database event
  attachments, and query cache lifetime handling.
- Fixed localization/test behavior for Windows and locale differences.
- Fixed PHP 8.4 deprecations and stricter static-analysis issues across core
  traits, models, helpers, controllers, and providers.
- Fixed JSON encoding fallbacks, CLI output newlines, dynamic model helpers, and
  null handling in several REST/model paths.
- Fixed static-analysis compatibility against Phalcon model stubs and cphalcon
  method signatures.
- Fixed `exit_500()` to use `http_response_code()` instead of direct header
  calls for better web-server compatibility.
- Fixed filter allow-list logic and nested filter/search key flattening.
- Fixed config, exception handler, debug output, cache key, and response
  handling edge cases discovered during the strict-typing pass.
- Fixed `Sprintf`, `mb_vsprintf`, named sprintf, JSON/color validators,
  dispatcher events, provider setup, and exception-handler tests.
- Fixed `BootstrapTest`, request headers, generated model tests, and locale
  assertions after PHP/Phalcon upgrades.
- Fixed audit/detail Blameable legacy-field support.
- Fixed `Oauth2` provider UUID typing and bindings.
- Fixed migration path handling and initial migration generation issues.
- Fixed `findInById`, model group handling, order validation, and related query
  trait edge cases.

### Security

- Expanded `SECURITY.md` with supported-version guidance, vulnerability
  reporting process, and toolchain notes.
- Improved ACL, role, impersonation, JWT, OAuth2, crypt, and security behavior
  typing while preserving the permission-policy model.
- Disabled stack trace and file display defaults in debug/security-sensitive
  paths from earlier 0.4.x work.

### Removed

- Removed deprecated dynamic config/model helpers and older field/translation
  table scaffolding paths.
- Removed obsolete unit tests for deprecated `Field`, `TranslateField`, and
  `TranslateTable` models.
- Removed obsolete frontend/admin/API/CLI view templates and unused modules from
  the package core.
- Removed `structure.sql`, outdated Sonar/Scrutinizer experiments, stale Docker
  assets, unused PowerShell scripts, and redundant generated/static assets from
  the 1.x package shape.
- Removed the custom MySQL `describeColumns()` workaround once upstream Phalcon
  fixed the issue.
- Removed the old `json_validate` helper and its tests.

## 0.4.46 - 2025-12-12

This is part of the 0.4.x maintenance line and was tagged after the 1.0.x line
started.

### Fixed

- Fixed query conditional logic when filters pass values with the `is empty`
  style operator.

## 0.4.45 - 2025-11-27

This is part of the 0.4.x maintenance line and was tagged after the 1.0.x line
started.

### Changed

- Refactored CSV writer initialization in the export trait.
- Removed Scrutinizer integration references from the 0.4.x documentation path.

## 0.4.44 - 2025-10-27

### Changed

- Updated package version and dependency requirements for the 0.4.x maintenance
  line.

### Removed

- Removed a commented debug line from the JWT session fallback path.

## 0.4.43 - 2025-10-23

### Fixed

- Fixed incorrect method usage in JWT session management.
- Made the REST error response status parameter nullable to prevent incorrect
  reason-phrase handling.

## 0.4.42 - 2025-04-21

### Added

- Added a JWT property to the base controller service annotations.
- Added customizable regex patterns to string sanitization helpers.

### Changed

- Refactored export CSV handling.
- Updated compatible dependency versions.

## 0.4.41 - 2025-04-07

### Changed

- Refined CORS header handling for better flexibility.

## 0.4.40 - 2025-04-04

### Added

- Added dispatcher params to request-data merging.

## 0.4.39 - 2025-04-03

### Changed

- Restricted value usage with `is` query operators.

## 0.4.38 - 2025-04-03

### Added

- Added boolean and null query operators.

### Fixed

- Fixed query operator mapping for PHQL compatibility.

## 0.4.37 - 2025-04-02

### Changed

- Refactored query handling in `DataLifeCycleTask`.

### Fixed

- Fixed spacing inconsistencies in `DataLifeCycleTask`.

## 0.4.36 - 2025-04-01

### Added

- Added `forceServerHttps` configuration support.

### Changed

- Refactored request HTTPS detection to support forced HTTPS behavior.

### Fixed

- Fixed table whitelist handling and query usage.

## 0.4.35 - 2025-04-01

### Added

- Enabled HTTPS detection based on forwarded headers.

## 0.4.34 - 2025-04-01

### Fixed

- Fixed fallback to `subCount()` when the normal count query fails.

## 0.4.33 - 2025-03-31

### Changed

- Refactored the data-life-cycle task with dynamic table support and hard-delete
  options.

## 0.4.32 - 2025-03-28

### Added

- Added a `getDateTime()` helper for safer `DateTime` modifications.

## 0.4.31 - 2025-03-28

### Added

- Added validation-backed handling for 401 unauthorized errors.

### Changed

- Switched JWT validation failures to `ValidatorException`.

### Fixed

- Fixed recursive exception behavior in JWT error handling.
- Simplified dispatcher forward-change detection.

## 0.4.30 - 2025-03-28

### Fixed

- Fixed JWT token validation in the security component.

## 0.4.29 - 2025-03-27

### Changed

- Refactored URL base-path construction in module handling.

## 0.4.28 - 2025-03-24

### Changed

- Refactored method signatures to use nullable types consistently.

### Deprecated

- Deprecated the `session.trans_sid_hosts` configuration option.

## 0.4.27 - 2025-03-22

### Changed

- Refactored method declarations to use nullable type hints.

## 0.4.26 - 2025-03-21

### Fixed

- Fixed role inheritance logic in `hasRole()`.

## 0.4.25 - 2025-03-19

### Fixed

- Fixed query behavior with the newer Phalcon filter system.

## 0.4.24 - 2025-03-11

### Added

- Added field aliasing support in query filters.
- Added `block_encryption_mode` support in database connection options.

### Changed

- Updated Composer dependencies.

### Removed

- Removed underscore-prefixed internal view parameters from REST responses.

## 0.4.23 - 2025-02-27

### Added

- Added a new dynamic joins query system.
- Added dynamic join support in negative/subquery conditions.
- Added cached request params.
- Added `recursiveStrReplace` helpers for nested array processing.
- Added whitelist support for `getWith()` style relation loading.

### Changed

- Refactored query handling for consistency.

### Fixed

- Rewrote SQL placeholders to ensure unique bind names in generated queries.
- Clarified exception messages around temporary query workarounds.

## 0.4.22 - 2025-01-28

### Added

- Added the new identity manager.
- Added missing model service provider registration.

### Changed

- Refactored identity handling and updated auth actions to use the newer
  identity manager path.
- Updated DI injectable service-provider annotations.
- Improved audit detail and Blameable audit behavior.

### Fixed

- Fixed Windows filesystem handling.
- Fixed Blameable audit detail behavior.

### Removed

- Removed the old identity implementation path.

## 0.4.21 - 2025-01-22

### Changed

- Updated table index metadata to return arrays.

### Fixed

- Fixed Blameable behavior.

## 0.4.20 - 2025-01-20

### Changed

- Improved query filtering.

## 0.4.19 - 2025-01-08

### Added

- Added data-life-cycle callable support.
- Added daily and weekly data-life-cycle policies.

## 0.4.18 - 2024-12-09

### Added

- Added `X-Authorization` to security/CORS headers.

### Changed

- Refactored the auth controller.

### Fixed

- Fixed missing related-record preservation behavior.

## 0.4.17 - 2024-11-25

### Fixed

- Fixed `keepMissingRelated` behavior with camel-case aliases and eager loading.

## 0.4.16 - 2024-11-20

### Added

- Added support for passing expose configuration to `getIdentity()`.
- Added string helpers for removing non-printable characters, normalizing line
  breaks, and sanitizing UTF-8.

### Changed

- Updated export handling to use the new string helper methods.

## 0.4.15 - 2024-11-13

### Fixed

- Fixed XLSX export edge cases.

## 0.4.14 - 2024-11-08

### Security

- Disabled stack trace and file display in debug mode by default.

### Changed

- Disabled backtrace output in the default debug behavior.

## 0.4.13 - 2024-11-04

### Added

- Added JWT and identity integration to login-as and logout-as flows.

### Fixed

- Fixed `FindIn` behavior.

## 0.4.12 - 2024-09-25

### Fixed

- Fixed filter fields with null values and `is empty` / `is not empty`
  operators.

## 0.4.11 - 2024-09-25

### Changed

- Adjusted query filter fields so bracketed fields are not auto-prefixed with
  model aliases.
- Allowed eager loading to use columns for the main record query.

## 0.4.10 - 2024-09-17

### Added

- Added model `subCount()` to execute count queries through subqueries when
  aggregated result conditions make normal counts unreliable.

## 0.4.9 - 2024-09-10

### Fixed

- Fixed database read-only warning-to-string conversion.

## 0.4.8 - 2024-09-03

### Added

- Added `allowRawValue` support for position validation.

### Fixed

- Fixed position behavior when using both raw SQL and ORM paths.

## 0.4.7 - 2024-07-10

### Added

- Added custom logical operators for query filters.

### Fixed

- Fixed the legacy filtering path.
- Fixed master/slave read-only database configuration.

## 0.4.6 - 2024-07-05

### Fixed

- Fixed a maintenance regression in the 0.4.x line.

## 0.4.5 - 2024-06-20

### Fixed

- Fixed non-JSON request handling.

## 0.4.4 - 2024-06-20

### Fixed

- Fixed relationship behavior for new related entities.

## 0.4.3 - 2024-06-18

### Fixed

- Fixed XLSX export.

## 0.4.2 - 2024-06-18

### Changed

- Upgraded package dependencies.

### Fixed

- Fixed CSV export.
- Fixed export regressions from the early 0.4.x line.

## 0.4.1 - 2024-05-21

### Added

- Added single-to-many assignment using primary keys.
- Added a PHPUnit bin wrapper.

### Changed

- Adjusted resultset return typing in the model path.

## 0.4.0 - 2024-05-14

### Added

- Added the modern PHP 8.2+/Phalcon 5.6+ typed core line that preceded the
  PhalconKit rename.
- Added scaffolding for REST controllers, services, models, abstracts, and
  interfaces from the database.
- Added `./vendor/bin/zemit` as the vendor binary for the Zemit-era package.
- Added model abstracts, abstract interfaces, relationships, validations, and
  generated migrations under package resources.
- Added database-first scaffolding improvements for eager-loading typings,
  interface `@property` / `@method` annotations, and generated model metadata.
- Added TypeScript scaffold task registration in permissions.
- Added Blameable injectable relationships to generated models.
- Added model cache/metadata driver separation by worker context.
- Added host-based router/module binding and module contextual views directory
  handling.
- Added request/provider support for JSON body handling and REST request arrays.
- Added MVC model restore, count endpoint, REST expose, restore/delete/reorder,
  and query param helpers.
- Added OAuth2, JWT, session, random, locale, profiler, escaper, mailer, MySQL,
  Redis, and local service-provider work across the 0.4.0 preparation period.
- Added normalized CLI task responses and database/scaffold CLI helpers.
- Added logger/database-event profiling tests and broad PHPUnit coverage for
  helpers, filters, locale, request, providers, models, and core services.

### Changed

- Bumped support to PHP 8.2+ and Phalcon 5.6.1+ in the old changelog baseline.
- Moved migrations out of `src/` and into `resources/`.
- Moved export dependencies to dev/suggested dependencies so export packages
  remained optional.
- Refactored auth actions, identity, dispatcher, bootstrap config, module
  routing, service providers, model relationships, export, REST cache, and
  query handling.
- Reactivated hydration casting and improved MySQL binary-field support.
- Switched code toward stricter PHP typing and static-analysis compliance.
- Removed cphalcon/PSR package dependencies where they were no longer needed.

### Fixed

- Fixed migrations, migration paths, CLI database seeds, auth controller
  behavior, dump functions, export bugs, status-code conflicts, and dispatcher
  ACL checks.
- Fixed UUID handling, model validation, filter behavior, relationship typings,
  blameable behavior typings, and Fractal typings.
- Fixed URL config paths, SMTP mailer behavior, CLI user management, and typed
  `readAttribute()` handling.
- Fixed scrutinizer/code-style/static-analysis issues from the typed migration.

### Removed

- Removed unused service providers, deprecated classes, unused tasks, old
  migrations, Travis config, standard docs, and stale phpunit backup files.
- Removed composer-included function constants and moved constants to model
  classes where appropriate.

## 0.2.3 - 2023-10-25

### Added

- Added database task optimize/analyze actions.
- Added model locale support and model validation helpers.
- Added file-system config support.
- Added starts-with, ends-with, does-not-start-with, and does-not-end-with
  filter operators.
- Added color validation.
- Added insert deployment task filtering.

### Changed

- Renamed the deployment task toward a database task.
- Moved user relationship handling toward Blameable model traits.
- Updated filter handling to allow array values.
- Improved export/download behavior and CSV header/row matching.
- Improved transformable callbacks to support nested callbacks.

### Fixed

- Fixed SimpleXLSXGen integration.
- Fixed positive and negative AND/OR behavior for multiple values on one field.
- Fixed mailer provider behavior.
- Fixed search, params, locale, deployment, between filters, router/filter
  config, and negative limit handling.
- Fixed application request dispatching between CLI and MVC contexts.
- Fixed model Blameable dates and validation trait behavior.

### Removed

- Removed function/composer constants in favor of model-owned constants.

## 0.1.33 - 2023-03-15

### Added

- Added default OpenAI service provider configuration.
- Added IMAP provider configuration.
- Added OpenAI organization ID config.
- Added export/download features.
- Added refresh-token support.
- Added default JWT service-provider changes and JWT injectable annotations.
- Added locale route-prefix/index handling.

### Changed

- Refactored bootstrap dotenv loading and base model startup behavior.
- Refactored model save-list handling.
- Updated dependencies and removed the external JWT library in favor of the
  package's current JWT path.

### Fixed

- Fixed `getParam()` logic, deployment defaults, config root/app paths, default
  `./zemit` app directory, environment path handling, dotenv loading, JWT
  behavior, missing role handling, and default locale route names.
- Prevented the dispatcher from calling actions with route-defined parameters.

## 0.1.31 - 2023-01-27

### Added

- Added max-upload-file-size utility support.

### Changed

- Added additional typing around utility behavior.

## 0.1.30 - 2023-01-11

### Added

- Added options support and model-mapping support.
- Added bootstrap router case-sensitivity configuration.

### Changed

- Optimized Composer autoloading.
- Moved suggested dependencies into `require-dev` where useful for development.
- Changed identity to use the new options and model-mapping support.

### Fixed

- Fixed bootstrap router behavior and router provider registration.
- Re-enabled router registration when not provided by configured service
  providers.

## 0.1.29 - 2023-01-06

### Fixed

- Fixed `identity.sessionFallback` handling.

## 0.1.28 - 2023-01-02

### Added

- Added AWS service-provider setup.

### Changed

- Refactored bootstrap dotenv/config/router handling for MVC versus CLI.
- Forced compatible Phalcon IDE stubs in the PHP 7.4/Phalcon 4 compatibility
  window.

### Fixed

- Fixed CLI default path config, config service provider behavior, PHP 7.4
  compatibility, and namespace consistency for locale classes.

## 0.1.27 - 2022-12-11

### Fixed

- Fixed preflight handling.

## 0.1.26 - 2022-11-15

### Added

- Added `Request::isCors()`, `Request::isPreflight()`, and
  `Request::isSameOrigin()` data exposure.
- Added response CORS header configuration.
- Added PATCH forwarding support for MVC REST.
- Added CORS and preflight dispatcher-event handling.
- Added MySQL dialect and Redis configuration updates.

### Changed

- Allowed random ordering via `rand()`.
- Improved regexp and contains-word filters.

### Removed

- Removed the old `isOptions()` path in favor of the preflight dispatch event.
- Removed Phalcon-injected `_url` from `getParams()`.
- Removed `phalcon/dd` from development dependencies.

## 0.1.25 - 2022-08-21

### Added

- Added new model query operators.

### Fixed

- Fixed `HAS_MANY` and `HAS_MANY_THROUGH` relationship behavior.

## 0.1.24 - 2022-06-22

### Fixed

- Fixed `Config::getModelClass()` mapping behavior.
- Adjusted return types for static-analysis compatibility.

## 0.1.23 - 2022-06-21

### Added

- Added authorization-header configuration.
- Added cache service-provider registration and controller annotations.
- Added session fallback for identity key/token retrieval.
- Added basic CMS proof-of-concept deployment task, models, migrations,
  controllers, permissions, and admin module structure.
- Added normalized CLI task responses and contextual CLI module directories.
- Added model security behavior planning around components, models, and
  permissions.
- Added host-based router support and default core-module planning.

### Changed

- Regenerated CMS model abstracts.
- Updated identity provider behavior to return JWT and identity together.
- Refactored REST controllers, status-code handling, dispatch forwarding,
  router hostname/module binding, and model cache/metadata driver selection.
- Changed backend module naming toward `admin`.

### Fixed

- Fixed model cache support for model mappers.
- Fixed module CLI behavior, controller annotation handling, dispatcher error
  handling, flat whitelist exposure, status-code trait behavior, and
  Phalcon-devtools migration behavior.

### Removed

- Removed the session model from the database path temporarily.
- Removed unused ClamAV controller annotations.

## 0.1.22 - 2022-04-07

### Fixed

- Fixed inherited role-list handling.

## 0.1.21 - 2022-04-07

### Changed

- Removed direct DI dependency from the database service provider.
- Added session default values in config.

## 0.1.20 - 2022-04-07

### Added

- Added the ability to enable or disable database replication services.
- Added distinct adapter config values.
- Added response service provider and events-manager service-provider refactor.
- Added REST controller skip-behavior support.
- Added early TypeScript CLI task scaffolding for database models.

### Changed

- Updated `mb_vsprintf()`.
- Refactored bootstrap PHP preparation and model/controller component config.
- Replaced deprecated `getModelName()` usage with model class-name handling.
- Refactored `saveEntity()` out of `saveAction()` for inheritance reuse.

### Fixed

- Fixed response service provider behavior.
- Fixed undefined CSS/JS collection handling.
- Fixed empty conditions to generate safe `where(1)` behavior.
- Fixed events-aware trait exception behavior.

### Removed

- Removed `php_value` usage from the default `.htaccess`.

## 0.1.19 - 2022-03-28

### Changed

- Updated Composer metadata.
- Extended the crypt service provider.

### Fixed

- Fixed profiler elapsed-second precision.
- Fixed inherited role-list handling.

## 0.1.18 - 2021-12-07

### Fixed

- Fixed empty inherited-role behavior.

## 0.1.17 - 2021-12-06

### Fixed

- Fixed recursive inherited-role behavior and optimized role fetching from the
  database.

## 0.1.16 - 2021-10-28

### Added

- Added `OPTIONS` preflight support.

## 0.1.15 - 2021-10-22

### Fixed

- Fixed column reorder and overwrite behavior.

## 0.1.14 - 2021-10-08

### Added

- Added `getListWith()` support.

### Changed

- Updated Flysystem integration.

## 0.1.13 - 2021-07-12

### Added

- Added Excel export functionality.

## 0.1.12 - 2021-06-29

### Fixed

- Fixed ambiguous column handling.

## 0.1.11 - 2021-05-13

### Fixed

- Fixed `or` / `and` filter handling.

## 0.1.10 - 2021-05-13

### Added

- Started OAuth2 client integration.

### Fixed

- Fixed `not between` filter PHQL generation.

## 0.1.9 - 2021-01-15

### Fixed

- Fixed `str_contains()` compatibility for PHP versions below 8.0.

## 0.1.8 - 2021-01-11

### Added

- Added export expose support.

## 0.1.7 - 2021-01-11

### Fixed

- Fixed relationship delete behavior.

## 0.1.6 - 2020-12-10

### Changed

- Moved several required PHP extensions to Composer suggestions.

## 0.1.5 - 2020-12-08

### Changed

- Downgraded and locked dotenv-related dependency versions for compatibility.
- Added default password reset template support.
- Added inherited-role appending for identity role lists.
- Added initial OAuth2 work and transform utilities.
- Added head/foot CSS and JS hooks to the default frontend.

### Fixed

- Fixed Phalcon 4.0/4.1 compatibility issues.
- Fixed relationship duplication for many-to-many aliases.
- Fixed recursive relationship save and relation whitelist behavior.
- Fixed user enumeration protection concerns in authentication flows.

### Removed

- Removed deprecated asset-manager method usage for Phalcon 4 compatibility.

## 0.1.4 - 2020-05-15

### Added

- Added CSV export support.
- Added session, JWT, OAuth2, random, local, request, profiler, config, and
  dispatcher provider work.
- Added position model trait support.
- Added REST count, restore, delete, reorder, expose, condition, and params
  foundations.

### Changed

- Moved the package toward Phalcon 4 and PHP 7.4 compatibility.
- Changed license to BSD-3-Clause.
- Refactored service providers, bootstrap/config preparation, REST behavior,
  dispatcher error handling, and request handling.
- Renamed the project toward the Zemit CMS package identity.

### Fixed

- Fixed position and `getParams()` behavior.
- Fixed dotenv upgrade issues and Phalcon 4 devtools support.
- Fixed filters, soft delete behavior, CSV export, DB config, and cyclic
  routing issues.

### Removed

- Removed unused tasks and stale vendors from the early Phalcon 4 migration.

## 0.1.3 - 2019-08-29

### Fixed

- Removed the Swift loader call.
- Fixed recursive iterator behavior.
- Fixed eager-loading implicit function behavior.

## 0.1.2 - 2018-11-30

### Fixed

- Fixed tag/release metadata issues.

## 0.1.1 - 2018-11-18

### Added

- Added locale/bootstrap event-manager, console, PSR-4, task, and CLI module
  foundations.
- Added early filters, routes, modules, POMO, HMVC, debug, URL, and router
  fixes.

### Fixed

- Removed broken dependencies.
- Fixed `.env` config path handling.

## 0.1.0 - 2017-12-18

### Added

- Added the initial Phalcon-based package foundation.
