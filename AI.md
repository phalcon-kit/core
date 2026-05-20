# AI-Assisted Development With PhalconKit

PhalconKit ships reusable AI guidance for two audiences:

- Developers building applications on top of PhalconKit.
- Maintainers contributing to `phalcon-kit/core`.

These assets are documentation and agent instructions only. They do not add a
runtime AI layer or change the PHP service provider API.

## Shipped Skills

The package includes local skills under `resources/skills/`:

- `resources/skills/phalconkit-app-developer/` helps agents work in an
  application that uses PhalconKit. Its Phalcon baseline reference points to
  official native Phalcon docs for MVC, DI, routing, controllers, models,
  relationships, filters, validation, security, sessions, logging, testing,
  and environment setup. Its framework reference explains app structure,
  bootstrap, config, providers, modules, REST controllers, models, migrations,
  CLI tasks, and WebSocket task wrappers. Its configuration reference explains
  root config, permission fragments, module registration, model aliases,
  provider overrides, role inheritance, and integration config.
  Its provider reference explains DI
  service registration, provider overrides, app-owned providers, and usage for
  each default provider. Its identity/security reference explains auth
  controllers, custom login endpoints, JWT/session identity, provider
  overrides, impersonation, role inheritance, ACL permissions, and
  config-attached security behaviors.
  Its REST reference documents real controller patterns such as save fields,
  filter fields, exposers, eager-loaded relation graphs, joins, and permission
  conditions, plus dynamic joins, advanced condition blocks, custom ordering,
  and high-volume list workflows. Its eager-loading reference explains
  `findWith()`, `findFirstWith()`, instance `load()`, controller
  `initializeWith()`, relation graph syntax, relation-level `QueryBuilder`
  closures, loader behavior, and current limits. Its transformer reference
  explains Fractal transformers, loaded-relation includes, serializers, and
  transformer-backed response shaping. Its behavior reference explains
  permission-attached controller behaviors, REST lifecycle hooks, condition
  removers, and custom query behaviors. Its CLI/WebSocket reference explains
  task wrappers, task permissions, Swoole handlers, Redis pub/sub bridges, live
  channel broadcasts, and watchers. Its model/scaffold reference explains
  migration helpers, database-first scaffold flow, generated abstract/interface
  ownership, enum generation, scaffold guessing rules, one-to-many and
  many-to-many relation behavior, relationship-aware assignment, eager loading,
  validation, model events, and model aliases. Its environment reference explains Docker Compose,
  PHP/Phalcon images, Apache/Nginx PHP-FPM and WebSocket proxying, service
  hostnames, `.env` values, Podman debugging, systemd supervision, and
  deployment checks. Its security/random, support-helper, model-mapping,
  model-behavior, routing/dispatch, filter/validation, logging, integration,
  OAuth2, OpenAI runtime, and testing references document the lower-level
  framework services that real apps rely on, with official Phalcon links where
  the PhalconKit convention extends native framework behavior.
- `resources/skills/phalconkit-core-maintainer/` helps agents work inside this
  core package. Its convention reference explains the package's own extension
  points and generated-file boundaries.

When this package is installed through Composer, the same paths are available
from:

```text
vendor/phalcon-kit/core/resources/skills/phalconkit-app-developer
vendor/phalcon-kit/core/resources/skills/phalconkit-core-maintainer
```

## Local Agent Usage

For local shell based agents, mount the skill by path in the runtime you
control. Example intent:

```text
Use the phalconkit-app-developer skill from vendor/phalcon-kit/core/resources/skills/phalconkit-app-developer to add a new module to this app.
```

For work on this repository, use:

```text
Use the phalconkit-core-maintainer skill from resources/skills/phalconkit-core-maintainer to review this provider change.
```

The exact mounting mechanism depends on the agent runtime. Keep execution local
when the work needs access to private application code, environment files,
database schemas, or credentials.

## Hosted Agent Usage

Hosted agent environments can use uploaded, versioned skill bundles. Package a
single skill directory as a bundle, review the content, upload it to the agent
platform, and attach the specific reviewed version to the hosted shell
environment.

Do not expose an open skill catalog directly to end users. Map reviewed skills
to specific product workflows, and require approval for actions that write
files, call external services, deploy code, modify databases, or handle secrets.

Reference: OpenAI's Skills guide documents local and hosted skill usage at
https://developers.openai.com/api/docs/guides/tools-skills.

## Example Prompts

Use prompts that name the skill and the framework behavior you want:

```text
Use the phalconkit-app-developer skill to add a reporting module that follows this app's existing module, provider, route, and view conventions.
```

```text
Use the phalconkit-app-developer skill to create a REST endpoint for invoices. Inspect the current models and controller patterns before editing.
```

```text
Use the phalconkit-app-developer skill to add a new event-like resource. Update the app config, permission config, exposers, API controller, model aliases, and tests using existing PhalconKit patterns.
```

```text
Use the phalconkit-app-developer skill to add a custom auth endpoint. Keep the core identity/JWT/session flow, update permission config, and verify role inheritance and protected actions.
```

```text
Use the phalconkit-app-developer skill to add an API controller for a resource with nested relations. Reuse the app's exposers, eager-loading, joins, and permission-condition patterns.
```

```text
Use the phalconkit-app-developer skill to review this eager-loaded API response. Check the findWith/findFirstWith relation graph, QueryBuilder constraints, exposed fields or transformer includes, and lazy-loading risks.
```

```text
Use the phalconkit-app-developer skill to add a transformer-backed API response. Match the app's eager-loaded relation aliases and avoid lazy-loading relation includes.
```

```text
Use the phalconkit-app-developer skill to add a WebSocket channel that broadcasts a model snapshot. Follow the app's Ws module, Swoole, Redis pub/sub, exposer, and permission conventions.
```

```text
Use the phalconkit-app-developer skill to add a new model-backed resource after a schema change. Run the app's migration/scaffold workflow, preserve concrete model customizations, update model aliases, and align validations with REST save fields.
```

```text
Use the phalconkit-app-developer skill to review a database-first scaffold change. Check generated relationships, one-to-many and many-to-many assignment behavior, enum generation, validations, eager loading, and concrete model business logic.
```

```text
Use the phalconkit-app-developer skill to review this Docker Compose and Apache/Nginx setup for a PhalconKit app with PHP-FPM, Swoole WebSockets, MySQL, and Valkey.
```

```text
Use the phalconkit-app-developer skill to review this model change. Check model mapping, generated scaffold ownership, relationship assignment, UUID/slug behavior, audit/security behavior, and validation helpers.
```

```text
Use the phalconkit-app-developer skill to add an OAuth2 login callback. Validate state, keep the identity/session flow, apply the app account-linking policy, and update provider config safely.
```

```text
Use the phalconkit-app-developer skill to add a domain service that calls the OpenAI provider. Keep controller code thin, use the current provider config contract, avoid logging secrets or prompt bodies, and add fake-client tests.
```

```text
Use the phalconkit-core-maintainer skill to review this service provider change for consistency with Bootstrap config and DI registration.
```

Real application examples are valuable. Distill them into short recipes in the
relevant skill reference instead of pasting large app files into `SKILL.md`.

## Safety Defaults

- Skills are privileged instructions. Review `SKILL.md` before use.
- Keep secrets in the local environment and out of prompts, logs, fixtures, and
  generated documentation.
- Prefer read-only exploration first, then make the smallest safe edit.
- Require human approval for destructive commands, migrations, deployments,
  dependency upgrades, network calls that send private data, and broad
  formatting sweeps.
- Do not ask an agent to hand-edit generated API docs. Regenerate them with
  `composer docs` only when that is the intended change.

## Coverage And Gaps

The current skill set covers the app structure, bootstrap/config flow,
provider/DI usage, integration provider catalog, REST controller patterns,
module wrappers, permission config, identity/auth/security recipes, low-level
security and UUID behavior, support helpers, routing/dispatch lifecycle,
filters/validation, logging/observability, exposers, eager loading and relation
query constraints, joins, controller behaviors, CLI/WebSocket recipes,
database-first model/scaffold recipes, model mapping, relationship assignment
and save behavior, model behavior internals, Fractal transformer recipes,
OAuth2 runtime usage, OpenAI runtime provider usage, testing recipes,
environment/deployment recipes, and core maintainer conventions.
It also includes a native Phalcon baseline map and quick native
filter/validator lists so app agents can check the underlying framework before
changing PhalconKit extensions.

The remaining follow-up is to keep the references synchronized when provider
contracts change, especially the OpenAI config key alignment noted in
`openai-runtime.md`.

The maintainer skill tracks this in
`resources/skills/phalconkit-core-maintainer/references/skill-coverage.md`.

## Maintainer Notes

Use `AGENTS.md` as the repo-local instruction file for agents editing this
package. It captures package structure, generated-documentation boundaries, and
validation commands.

This documentation pass does not change `src/Provider/OpenAi/ServiceProvider.php`,
OpenAI config keys, Composer dependencies, or any PHP runtime AI API. Plan
runtime AI service work separately from reusable skill and agent-documentation
work.

When adding new reusable skills:

- Place each skill under `resources/skills/<skill-name>/`.
- Include exactly one `SKILL.md`.
- Put detailed framework recipes in `references/` and link them directly from
  `SKILL.md`.
- Keep the trigger description precise so agents load the skill only when it is
  relevant.
- Avoid embedding project secrets, private customer details, or large generated
  references.
- Prefer concise workflow rules over long tutorials.
