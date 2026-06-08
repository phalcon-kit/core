# How Phalcon Kit Organizes Your App

Phalcon Kit gives a Phalcon application a repeatable shape. The point is not to
hide Phalcon. The point is to make the common decisions once so each project can
focus on its models, API resources, workflows, and permissions.

Official Phalcon references:

- MVC: https://docs.phalcon.io/5.14/mvc/
- Dependency injection: https://docs.phalcon.io/5.14/di/
- Loader/autoloading: https://docs.phalcon.io/5.14/autoload/
- Routing: https://docs.phalcon.io/5.14/routing/
- Models: https://docs.phalcon.io/5.14/db-models/

## Request Flow

A normal HTTP request follows this shape:

1. `public/index.php` requires the project entrypoint.
2. The project entrypoint loads `loader.php`.
3. `App\Bootstrap` configures PhalconKit with app-owned config.
4. Service providers register the DI services.
5. The selected module handles routing, dispatch, controller execution, and the
   response.

CLI and WebSocket entrypoints use the same bootstrap with a different mode:

```php
new Bootstrap('cli');
new Bootstrap('ws');
```

That means your API controllers, CLI tasks, and WebSocket tasks can use the same
database services, identity rules, model aliases, logger, and app config.

## Where To Put Code

The most important rule is simple: generated code mirrors the database; app code
owns behavior.

- `Config/`: app-owned configuration, provider overrides, permissions, modules,
  aliases, and integrations.
- `Models/Abstracts/`: generated schema layer. Regenerate it when the database
  changes.
- `Models/`: app-owned concrete models and business logic.
- `Modules/Api/Controllers/`: resource-specific REST field policies and
  workflow endpoints.
- `Modules/Cli/Tasks/`: operational tasks, imports, exports, maintenance jobs,
  and scaffolding entrypoints.
- `Modules/Ws/Tasks/`: WebSocket task handlers.
- `resources/migrations/`: database migration history.

For example, if the `project` table gains a `status` column, regenerate the
abstract model so accessors and validation match the database. If the app needs
`Project::archive()`, write that method in the concrete `Project` model.

## Common Customizations

Most real apps customize these pieces:

- Config classes extending `PhalconKit\Bootstrap\Config`.
- Service providers extending `PhalconKit\Provider\AbstractServiceProvider`.
- Concrete models extending generated abstract models.
- API controllers extending the app API base controller.
- Permission config classes merged into the app config.
- Fractal transformers for stable API output.
- Model behaviors for reusable lifecycle logic.

Prefer app-owned extensions over editing vendor code or generated files.

## Still Normal Phalcon

When unsure, start from the native Phalcon concept:

- DI services are still Phalcon DI services.
- Models are still Phalcon ORM models.
- Controllers still run through the Phalcon dispatcher.
- Validation still uses Phalcon validation primitives.
- Routing still uses Phalcon routing and dispatcher semantics.

PhalconKit adds conventions, generators, helpers, and defaults around those
components. It does not replace the underlying framework.
