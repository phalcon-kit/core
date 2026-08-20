# How Phalcon Kit Organizes Your App

Phalcon Kit gives a Phalcon application a repeatable shape. It does not hide
Phalcon; it makes recurring ownership decisions explicit so projects can focus
on models, APIs, workflows, and permissions.

## The Mental Model

<div class="grid cards" markdown>

-   :material-cog-outline:{ .lg .middle } **Bootstrap composes**

    It loads app config, creates the DI container, registers providers, and
    starts the selected runtime.

-   :material-database-outline:{ .lg .middle } **The schema describes data**

    Migrations and the live database drive generated model structure.

-   :material-domain:{ .lg .middle } **Application code owns behavior**

    Concrete models and services hold invariants, workflows, and integrations.

-   :material-api:{ .lg .middle } **Controllers define HTTP policy**

    Controllers choose fields, filters, relationships, permissions, and response
    behavior.

</div>

Official Phalcon references:

- [MVC](https://docs.phalcon.io/latest/mvc/){:target="_blank"}
- [Dependency injection](https://docs.phalcon.io/latest/di/){:target="_blank"}
- [Loader and autoloading](https://docs.phalcon.io/latest/autoload/){:target="_blank"}
- [Routing](https://docs.phalcon.io/latest/routing/){:target="_blank"}
- [Models](https://docs.phalcon.io/latest/db-models/){:target="_blank"}

## HTTP Request Flow

```text
HTTP request
  → public/index.php
  → bootstrap.php
  → Composer autoloader
  → App\Bootstrap
  → App\Config\Config
  → service providers / DI
  → module and router
  → dispatcher
  → controller action
  → Phalcon response
```

The steps have distinct failure modes. A web-server 404 occurs before
bootstrap. A missing DI service occurs during composition or controller use. A
REST policy error occurs after routing and dispatch.

CLI and WebSocket entrypoints reuse the same composition root with another
mode:

```php
new Bootstrap('cli');
new Bootstrap('ws');
```

That lets controllers, CLI tasks, and WebSocket tasks share database services,
model aliases, logging, identity rules, and application configuration.

## Ownership By Directory

| Path | Owner | Typical contents |
| --- | --- | --- |
| `src/Bootstrap.php` | Application | Small composition overrides |
| `src/Config/` | Application | Modules, providers, aliases, permissions, integrations |
| `src/Models/Abstracts/` | Generator | Columns, relationships, validation, metadata |
| `src/Models/` | Application | Domain behavior and custom relationships |
| `src/Modules/Api/Controllers/` | Application | REST/query policy and workflow actions |
| `src/Modules/Cli/Tasks/` | Application | Imports, exports, maintenance, scheduled work |
| `src/Modules/Ws/Tasks/` | Application | WebSocket task handlers |
| `bin/` | Application | Runtime entrypoints such as the project CLI |
| `scripts/` | Application | Migration, scaffolding, and maintainer helpers |
| `resources/migrations/` | Application | Ordered schema history |
| `vendor/phalcon-kit/core/` | Package manager | Framework code; never edit in an app |

The most important rule is: **generated code mirrors the database; app-owned
code owns behavior**.

If the `project` table gains a `status` column, regenerate the abstract model so
accessors and validation match the schema. If a project needs an `archive()`
transition, implement that in the concrete `Project` model.

## Decide Where New Code Belongs

| Requirement | Best starting point |
| --- | --- |
| Validate one model’s state transition | Concrete model |
| Coordinate several models in a transaction | Application/domain service |
| Call an external API from several workflows | Service plus DI provider |
| Restrict client-visible or writable fields | REST controller policy |
| Restrict which rows an identity can query | Permission conditions |
| Produce a stable public JSON shape | Transformer |
| Run an operational batch | CLI task calling a service |
| React to a reusable lifecycle event | Listener or model behavior |

Avoid large controllers that perform persistence, integration calls, and output
formatting inline. Controllers should adapt transport intent to application
operations.

## Extend Through Providers

Providers are the boundary between configuration and constructed services. A
provider registers a stable service name in the Phalcon Kit DI container:

```php
use App\Service\ReportExporter;
use Phalcon\Contracts\Db\Adapter\Adapter as AdapterContract;
use Phalcon\Contracts\Logger\Logger as LoggerContract;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

final class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'reportExporter';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), static function () use ($di) {
            return new ReportExporter(
                $di->getTyped('db', AdapterContract::class),
                $di->getTyped('logger', LoggerContract::class)
            );
        });
    }
}
```

Register app services in app config. Override a core provider only when the app
intentionally replaces that service contract.

## Generated Versus Concrete Models

```text
ProjectAbstract      ← regenerated from the schema
      ↑
Project              ← app-owned behavior
      ↑
controllers/services ← use the concrete model
```

Never place durable business logic in a generated abstract. Regeneration should
be routine and reviewable, not dangerous.

## Common Extension Points

Most applications customize:

- config classes extending `PhalconKit\Bootstrap\Config`;
- providers extending `PhalconKit\Provider\AbstractServiceProvider`;
- concrete models extending generated abstract models;
- API controllers extending the app API base controller;
- permission config and controller attributes;
- transformers for stable API representations;
- behaviors and listeners for reusable lifecycle logic.

Prefer these app-owned extension points over vendor patches. When the same
missing capability affects several applications, contribute it to core with
tests and documentation.

## Still Normal Phalcon

- DI services are Phalcon DI services.
- Models are Phalcon ORM models.
- Controllers run through the Phalcon dispatcher.
- Validation uses Phalcon validation primitives.
- Routing follows Phalcon routing and dispatcher semantics.
- Responses implement Phalcon response contracts.

Phalcon Kit adds conventions, generators, typed helpers, and defaults around
those components. Native Phalcon documentation remains relevant.

## Continue Building

- [Configuration](configuration.md) turns the composition model into concrete
  app config and providers.
- [Database And Scaffolding](database-scaffolding.md) explains the generated
  model boundary.
- [Resource Walkthrough](resource-walkthrough.md) follows one resource across
  every layer.
- [Developer Cookbook](cookbook.md) provides copy-and-adapt recipes.
