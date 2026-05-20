# PhalconKit Core Conventions

Use this reference when changing `phalcon-kit/core` or documenting how users
should build on it.

## Bootstrap Flow

`PhalconKit\Bootstrap` is the central runtime coordinator:

1. Select mode: `mvc`, `cli`, or `ws`.
2. Create the default DI (`FactoryDefault` or `FactoryDefault\Cli`).
3. Register the config service.
4. Register service providers from `config.providers`.
5. Boot providers.
6. Register modules.
7. Register the router.

Changes to config, providers, modules, or routers should respect this order.
When documenting app customization, point users to app config or an app
bootstrap subclass instead of editing core bootstrap logic.

## Config

`PhalconKit\Bootstrap\Config` defines default framework config and merges
constructor input through append-merge behavior. Defaults cover app paths,
response headers, providers, modules, router defaults, database, cache,
identity, permissions, and integration settings.

Core code should use:

- `$config->pathToArray('section.path')` for nested arrays.
- `Env::get(...)` for environment-backed defaults.
- Grouped first-level config keys; tests expect first-level entries to be
  `Phalcon\Config\Config` objects.

Do not introduce one-off environment parsing outside config unless there is a
specific runtime reason.

Provider config is an expected-provider to actual-provider map:

```php
'providers' => [
    Provider\Response\ServiceProvider::class =>
        Env::get('PROVIDER_RESPONSE', Provider\Response\ServiceProvider::class),
],
```

`Bootstrap::registerServices()` registers the actual class values, but
append-merge replacement uses the array key. Document app replacements as:

```php
'providers' => [
    \PhalconKit\Provider\Response\ServiceProvider::class =>
        \App\Provider\Response\ServiceProvider::class,
],
```

Provider values must be class-strings. Do not document `false` as a valid way
to disable a provider unless bootstrap registration behavior changes.

Keep app-facing configuration recipes in
`resources/skills/phalconkit-app-developer/references/configuration.md` aligned
with `Bootstrap\Config`, permission config fragments, provider replacement
rules, model aliases, and module registration behavior.

## Providers

Providers extend `PhalconKit\Provider\AbstractServiceProvider` and implement
`register(DiInterface $di)`. The constructor requires a non-empty
`$serviceName` and calls `configure()`.

A provider is the framework's pre-configured DI registration layer, not the
domain service itself. It should read config, build the service, and inject the
ready-to-use object into DI under `$serviceName` so controllers, tasks,
components, and other injectables can consume it by service name.

Provider rules:

- Set a stable `$serviceName`; injected properties and downstream code depend
  on DI service names.
- Use `$di->setShared($this->getName(), function () use ($di) { ... })` for
  shared services.
- Pull options from `config` with `pathToArray()`.
- Assert expected dependency types after retrieving services from DI.
- Register provider classes through `Bootstrap\Config` under `providers`.

If replacing or fixing a provider, check `src/Di/InjectableProperties.php`,
`src/Di/AbstractInjectable.php`, provider tests, and config keys for matching
types and names.

When replacing a core provider, preserve the DI service contract. A replacement
for `Provider\Response\ServiceProvider` should still set
`$serviceName = 'response'` and return a compatible response instance.

Keep the app-facing provider catalog in
`resources/skills/phalconkit-app-developer/references/providers.md` aligned
with the default provider map, service names, config sections, and notable
implementation gaps such as placeholder providers.

## Modules And Routing

MVC modules extend `PhalconKit\Mvc\Module`; CLI modules extend
`PhalconKit\Cli\Module`.

`Mvc\Module` registers namespaces derived from the module class:

- `<Namespace>\Controllers`
- `<Namespace>\Models`
- `<Namespace>\Transformers`
- `PhalconKit\Models`

It also sets dispatcher defaults, view directories, URL base path, router
defaults, not-found route, and extra slash handling.

App modules commonly extend the pre-defined core modules and override
`getNamespaces()` to add shared app namespaces, especially `App\Models`, while
returning `parent::getNamespaces()` so core module namespaces and
`PhalconKit\Models` remain available.

`Bootstrap\Router` adds base routes and locale routes; `Mvc\Router` mounts
module and hostname routes from config. Router changes should preserve module
auto-mounting and locale support.

## REST Controllers

`PhalconKit\Mvc\Controller\Restful` combines REST actions, export/expose,
model lookup, and query-building traits. `PhalconKit\Modules\Api\Controller`
extends that RESTful base.

Controller conventions:

- Default model lookup derives from the controller name and registered model
  namespaces.
- Use `initializeSearchFields()`, `initializeExposeFields()`, and
  `initializeWith()` to constrain default query and response behavior.
- Query setup is event-driven around `rest:*` events; behavior classes can
  adjust query phases without rewriting the controller.

When changing REST behavior, inspect the related trait in
`src/Mvc/Controller/Traits/` and the behavior classes in
`src/Mvc/Controller/Behavior/`.

## Models

`PhalconKit\Mvc\Model` layers many framework traits on top of Phalcon models:
options, cache, snapshot, replication, soft delete, position, security,
blameable fields, slug, UUID, validation, relationships, eager loading, and
more.

Core generated model pattern:

- Abstract generated class under `src/Models/Abstracts/`.
- Interface under `src/Models/Interfaces/`.
- Concrete class under `src/Models/` with custom initialization/validation.

Concrete classes generally call `parent::initialize()` and
`addDefaultRelationships()`, then use `genericValidation()` and
`addDefaultValidations()` in `validation()`.

Avoid broad edits to generated abstracts unless the task is explicitly about
the generated model layer or scaffold output.

## CLI

`PhalconKit\Cli\Module` registers `<Namespace>\Tasks`, `<Namespace>\Models`,
and `PhalconKit\Models`. Tasks extend `PhalconKit\Cli\Task` or the module task
base, expose `*Action()` methods, and may provide `$cliDoc` usage text.

Keep CLI task docs and Bootstrap's CLI usage text aligned when adding or
renaming core tasks.

## Documentation And Skills

- `docs/classes/`, `docs/functions/`, and `docs/mkdocs_menu.yml` are generated
  API docs.
- Root markdown files are hand-authored maintainer/user docs.
- Reusable skills belong in `resources/skills/<skill-name>/`.
- Detailed skill recipes belong in `references/`, linked directly from
  `SKILL.md`.

Documentation that teaches framework usage should prefer the app-facing skill
reference when it is intended for downstream users, and this core convention
reference when it is intended for maintainers.
