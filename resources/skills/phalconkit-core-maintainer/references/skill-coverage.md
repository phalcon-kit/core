# Skill Coverage And Gaps

Use this reference when improving PhalconKit's reusable AI skills, README,
`AI.md`, or docs resources. It records the current app-facing coverage and the
highest-value follow-up work.

## Current Coverage

The app-developer skill now covers:

- Real app structure under `app/`, including config, permissions, generated
  model layers, modules, providers, bootstrap, and entrypoints.
- Official Phalcon baseline map: native docs for MVC, DI, routing,
  controllers, models, relationships, filters, validation, security, sessions,
  logging, testing, environment setup, plus quick native filter and validator
  lists.
- Bootstrap and config flow: app bootstrap installs `App\Config\Config` before
  provider/module registration.
- Root config composition: modules, router defaults, locale, provider
  overrides, model aliases, integration config, role inheritance, and
  per-resource permission fragments.
- Provider lifecycle and catalog: providers as pre-configured DI services,
  override rules, service names, controller/component usage, and provider
  replacement constraints.
- Real provider recipes: replacing the core `identity` provider with an app
  manager that persists identity through an app model, and adding an app-owned
  `firebase` provider with its own config section.
- Identity/security recipes: core auth actions, custom auth endpoints,
  participant-style login without password, JWT/session identity, identity
  provider overrides, impersonation, role inheritance, effective ACL roles,
  permission policy components, and config-attached security behaviors.
- REST vs RESTful controller selection.
- Real API controller patterns: app API abstract controller, exposers, save
  fields, filter fields, search fields, eager-loaded relations, joins, and
  permission conditions, plus dynamic joins, model-derived save fields,
  before-assign sanitization, advanced condition blocks, high-volume limits,
  computed ordering, and two-phase custom sorting.
- Fractal transformer recipes: transformer module placement, app base
  transformers, loaded-relation includes, default includes, RawArraySerializer
  expectations, and controller usage.
- Eager-loading recipes: `findWith()`, `findFirstWith()`, instance `load()`,
  controller `initializeWith()`, relation graph syntax, relation-level
  `QueryBuilder` constraints, loader behavior, supported relation types,
  transformer/exposer integration, and implementation limits.
- Controller behavior patterns: permission-driven attachment, REST lifecycle
  hooks, custom condition behaviors, condition/query removers, `Skip*` vs
  `Remove*` guidance, and row-visibility warnings.
- Module wrappers for Api, Cli, Ws, and Frontend modules that add shared app
  namespaces while preserving parent module namespaces.
- CLI and WebSocket recipes: abstract task wrappers, error wrappers, task
  permissions, WS router defaults, Swoole settings, channel protocols, Redis
  pub/sub bridges, domain snapshot broadcasts, watcher timers, WebSocket
  entrypoints, Podman debug runs, and systemd supervision.
- Environment/deployment recipes: Docker Compose local stacks, PHP-FPM and
  Swoole image variants, Phalcon extension builds, Apache/Nginx PHP-FPM and
  WebSocket proxying, service hostnames, `.env` values, secret handling,
  runtime commands, and deployment checks.
- Frontend SPA fallback pattern for app error controllers and compiled
  frontend host views.
- Model and scaffold lifecycle: database-first workflow, migration helper
  scripts, scaffold command modes, generated model ownership, scaffold outputs,
  relationship and validation guessing rules, concrete model contracts,
  relationship-aware assignment, one-to-many and many-to-many save behavior,
  eager loading, custom relationships, validation rules, model events,
  Redis/WebSocket publish hooks, domain helpers, and model alias config.
- Core-maintainer conventions for bootstrap, config, providers, modules,
  REST controllers, models, CLI, generated docs, and skill maintenance.

## Coverage Added From Codebase Scan

The full codebase scan has now been converted into app-facing references:

- `security-and-random.md`: security service, UUIDv7, model UUID behavior,
  hashing, JWT, crypt/cookies, and response security headers.
- `support-helpers.md`: autoload optimization, Composer-loaded functions,
  helper facade, Env, Php, Utils, Slug, and null-aware collection policies.
- `model-mapping.md`: core-to-app model mapping, `models` service usage,
  interface expectations, provider usage, and tests.
- `model-behaviors.md`: base model behavior stack, relationship assignment,
  related saves, dirty relations, validation helpers, model ACL, audit,
  soft-delete, position, replication, snapshots, slug, UUID, JSON, and hash
  helpers.
- `routing-and-dispatch.md`: MVC/CLI/WS route defaults, module/locale/hostname
  routes, error routes, dispatcher plugins, CORS/preflight, security dispatch,
  and dispatch logging.
- `filters-and-validation.md`: custom filters, JSON/color validators, model
  validation helpers, REST filter/search conditions, permission/soft-delete
  conditions, and advanced condition blocks.
- `logging-and-observability.md`: logger services, named loggers, dispatch
  logs, database logging/profiling, debug output, CLI/WS logs, and no-log
  secrets list.
- `integrations.md`: integration provider service names, config sections,
  safe usage boundaries, storage, Redis/Swoole, mail/IMAP, reCAPTCHA, and
  ClamAV.
- `oauth2.md`: OAuth2 provider services, config, authorization redirects,
  state validation, callbacks, identity linking, app-owned controllers, and
  tests.
- `testing.md`: validation command selection, provider/config tests,
  permission tests, REST smoke tests, model/scaffold tests, eager-loading
  tests, long-running job tests, and documentation claim checks.
- `openai-runtime.md`: current `openAi` provider contract, dependency boundary,
  app service boundaries, secret/data handling, tests, and canonical/legacy
  config key handling.
- `phalcon-baseline.md`: official native Phalcon documentation map and compact
  native filter/validator lists for places where PhalconKit extends framework
  behavior.

## Remaining Follow-Up

- Add more app-level test fixture examples when real applications expose stable
  reusable patterns.
- Revisit integration provider catalog entries when service names, config
  sections, or optional dependency requirements change.

## README And Public Copy Guidance

Public docs should make PhalconKit feel easy by emphasizing concrete defaults,
not by overclaiming.

Use supportable trust signals:

- CI status badge.
- Unit tests under `tests/Unit`.
- Composer validation scripts: `phpunit`, `psalm`, `psalm:taint`, `phpcs`, `skeleton`.
- Pre-configured providers and modules.
- Real app conventions captured in reusable skills.
- Generated API docs and official documentation site.

Avoid unsupported phrases:

- `bulletproof`
- `bug-free`
- `fully secure`
- `guaranteed`
- `production-ready for every app`

Prefer:

- `convention-driven`
- `tested baseline`
- `pre-configured`
- `predictable extension points`
- `AI-ready documentation`
- `designed for real applications`

## Next Pass Priority

1. Add narrow example tests for provider overrides, REST permission behavior,
   relationship assignment, and OAuth2 callback policy when a stable example
   app is available for extraction.
2. Keep README and skill copy focused on supportable claims: tested baseline,
   convention-driven defaults, and predictable extension points.
