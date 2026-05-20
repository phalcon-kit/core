---
name: phalconkit-app-developer
description: Use when building, debugging, or reviewing an application that depends on PhalconKit, including native Phalcon baseline references, app structure, modules, service providers, identity/auth controllers, bootstrap config, permission config, controller behaviors, REST controllers, exposers, eager loading, Fractal transformers, models, migrations, scaffolding, model behaviors, model mapping, UUID/security helpers, support helpers, filters, validation, routing, dispatch, logging, OAuth2, OpenAI runtime, integration providers, CLI tasks, WebSocket tasks, Docker/local environment, deployment config, testing, and package integration.
---

# PhalconKit App Developer

Use this skill when working in an application that installs `phalcon-kit/core`.
The goal is to follow PhalconKit conventions before inventing app-specific
structure.

## First Pass

1. Inspect the app's Composer config, bootstrap entrypoints, `.env` examples,
   modules, and service providers.
2. Check whether `vendor/phalcon-kit/core` is installed and use its source as
   the convention reference when available.
3. Search before editing: routes, modules, providers, migrations, model
   abstracts, CLI tasks, and tests often have established local patterns.
4. Check `git status --short` and preserve unrelated user changes.

## Framework Usage Reference

Read `references/phalcon-baseline.md` when a task needs native Phalcon
semantics behind PhalconKit behavior, official Phalcon documentation links, or
the current native filter/validator quick lists.

Read `references/framework-usage.md` when the task asks how to build something
with PhalconKit, when adding modules/providers/models/controllers/tasks, or when
the app has no obvious local example.

Read `references/configuration.md` when adding or changing app config, module
registration, model aliases, provider overrides, permission configs, ACL roles,
or custom integration config.

Read `references/providers.md` when adding, overriding, disabling, debugging,
or using PhalconKit service providers and DI services.

Read `references/integrations.md` when using built-in integration providers
such as AWS, FileSystem, Redis, Swoole, Mailer, IMAP, ClamAV, reCAPTCHA,
OAuth2, or OpenAI.

Read `references/identity-and-security.md` when adding or reviewing auth
controllers, login endpoints, JWT/session identity flow, impersonation,
role-based access, ACL permission config, or security middleware behavior.

Read `references/security-and-random.md` when touching low-level security
services, UUIDv7 generation, model UUID behavior, password hashing, JWT config,
crypt/cookies, or response security headers.

Read `references/oauth2.md` when adding or debugging OAuth2 provider config,
authorization redirects, callbacks, state validation, account linking, or
identity login through OAuth2.

Read `references/openai-runtime.md` when using the runtime `openAi` provider,
app-owned OpenAI domain services, OpenAI provider config, or OpenAI testing and
secret-handling rules.

Read `references/rest-api-controllers.md` when adding or changing REST
resources, API controllers, exposers, joins, filters, or permission conditions.

Read `references/routing-and-dispatch.md` when changing routes, module routing,
dispatcher plugins, CORS/preflight behavior, maintenance mode, request helpers,
response headers, or route-related errors.

Read `references/filters-and-validation.md` when handling input filters,
sanitizers, validators, REST filter/search conditions, advanced condition
blocks, or model validation.

Read `references/eager-loading.md` when adding, reviewing, or debugging
`findWith()`, `findFirstWith()`, `load()`, relation graphs, eager-loaded API
responses, or relation-level `QueryBuilder` constraints.

Read `references/transformers.md` when adding or changing Fractal
transformers, transformer-backed API output, includes, serializers, or
relation-loaded response shaping.

Read `references/behaviors.md` when adding, attaching, reviewing, or debugging
permission-driven controller behaviors, query condition removers, custom REST
lifecycle hooks, or role-specific query changes.

Read `references/cli-and-websocket.md` when adding or changing CLI tasks,
WebSocket tasks, Swoole server handlers, Redis pub/sub bridges, live channel
broadcasts, module error wrappers, or task permissions.

Read `references/models-and-scaffolding.md` when adding or changing models,
generated abstract layers, model interfaces, migrations, scaffold commands,
model aliases, validations, relationships, model events, or domain helpers.

Read `references/model-behaviors.md` when changing relationship assignment,
model events, soft delete, audit/blameable behavior, position, replication,
snapshot behavior, slug/UUID behavior, cache behavior, or model-level ACL.

Read `references/model-mapping.md` when replacing core PhalconKit models with
app models, updating config `models`, or testing mapped model classes.

Read `references/environment.md` when adding or changing Docker Compose,
Dockerfiles, Apache/Nginx config, PHP extensions, `.env` examples, service
hostnames, Swoole runtime settings, Redis/Valkey, MySQL, TLS/proxy settings, or
deployment config.

Read `references/support-helpers.md` when using app loader conventions,
Composer-loaded helper functions, `Helper`, `Env`, `Php`, `Utils`, `Slug`, or
null-aware collection policy helpers.

Read `references/logging-and-observability.md` when changing logger config,
named loggers, SQL logging, profiler behavior, dispatch logging, debug
rendering, CLI task logs, or WebSocket logs.

Read `references/testing.md` when choosing validation commands or adding tests
for providers, config, permissions, REST controllers, model mapping,
scaffolding, eager loading, transformers, CLI tasks, or WebSocket behavior.

## Implementation Rules

- Prefer the application's existing namespace and directory layout.
- Register services through PhalconKit provider/config patterns instead of
  creating ad hoc global state.
- Keep environment-driven config in `.env` or app config files; never hard-code
  secrets.
- Match existing module boundaries for Frontend, Admin, Api, Cli, Oauth2, or
  custom modules.
- For models and migrations, follow the app's generated abstract/interface
  pattern when it exists.
- For CLI work, follow the local task/dispatcher conventions before adding new
  command wiring.
- Do not change vendor files directly. Patch the app or upgrade dependencies
  through Composer when required.
- If a real app has stronger local conventions than the generic reference,
  follow the real app and keep the change consistent.

## Validation

Use the app's own Composer scripts first. If none exist, check for the common
PhalconKit wrappers:

- `composer phpunit`
- `composer psalm`
- `composer phpcs`
- `composer skeleton`

Some applications also define `composer phpstan`, Pest, Codeception, or other
project-specific checks. Use local scripts when they exist.

For documentation-only changes, run `git diff --check` and targeted link/path
searches instead of full runtime checks unless the docs include executable
examples.

## Safety

- Treat database migrations, deployments, dependency upgrades, network calls,
  and destructive shell commands as approval-required work.
- Keep credentials out of prompts, docs, fixtures, logs, screenshots, and test
  artifacts.
- Prefer local execution for private application repositories.
