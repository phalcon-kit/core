# Configuration

PhalconKit applications are configured through app-owned config classes. Keep
secrets in environment files and keep application structure in code:

- modules
- providers
- router defaults
- model aliases
- permissions and roles
- locale defaults
- service integrations

Official Phalcon references:

- Config: https://docs.phalcon.io/5.14/config/
- Dependency injection: https://docs.phalcon.io/5.14/di/
- Routing: https://docs.phalcon.io/5.14/routing/

## Environment

Example `.env` values:

```ini
APP_NAME="My App"

DATABASE_HOST=127.0.0.1
DATABASE_DBNAME=app
DATABASE_USERNAME=app
DATABASE_PASSWORD=app
```

Keep secrets and machine-specific paths in environment files. Keep module
registration, providers, aliases, and policy in config classes so they can be
reviewed and versioned.

## App Config

```php
<?php

namespace App\Config;

use PhalconKit\Support\Env;

final class Config extends \PhalconKit\Bootstrap\Config
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        $data = $this->internalMergeAppend([
            'app' => [
                'name' => Env::get('APP_NAME', 'My App'),
            ],
            'modules' => [
                \PhalconKit\Mvc\Module::NAME_API => [
                    'className' => \App\Modules\Api\Module::class,
                    'path' => APP_PATH . '/Modules/Api/Module.php',
                ],
            ],
        ], $data);

        parent::__construct($data, $insensitive);
    }
}
```

Use `internalMergeAppend()` when the app wants to keep PhalconKit defaults and
append or override only the app-owned parts.

## Modules

Modules define runtime boundaries. Common module names are:

- `frontend`
- `admin`
- `api`
- `oauth2`
- `cli`
- `ws`

Register app modules explicitly:

```php
'modules' => [
    \PhalconKit\Mvc\Module::NAME_API => [
        'className' => \App\Modules\Api\Module::class,
        'path' => APP_PATH . '/Modules/Api/Module.php',
    ],
],
```

## Provider Overrides

Provider overrides are config-first. Replace a core provider by keeping the
core provider class as the key. Register new app services with the app provider
as both key and value.

```php
'providers' => [
    \PhalconKit\Provider\Identity\ServiceProvider::class =>
        \App\Provider\Identity\ServiceProvider::class,

    \App\Provider\Firebase\ServiceProvider::class =>
        \App\Provider\Firebase\ServiceProvider::class,
],
```

Common provider categories include database, cache, session, identity, ACL,
router, request/response, logger/loggers, mailer, Redis, Swoole, OpenAI, OAuth,
filesystem, translation, view, Volt, URL, and helpers.

Use app providers when a service needs app configuration, app credentials, or a
different implementation. Avoid replacing a core provider just to change one
runtime option when config already supports it.

## Event Listeners

Use `eventsManager.listeners` for app-owned listeners that should attach to the
shared Phalcon events manager during bootstrap:

```php
'eventsManager' => [
    'listeners' => [
        'dispatch' => [
            [
                'class' => \App\Listeners\SecurityHeaders::class,
                'priority' => 200,
            ],
            [
                'service' => 'auditDispatchListener',
                'priority' => 100,
            ],
        ],
        'db' => [
            \App\Listeners\QueryCorrelation::class,
        ],
    ],
],
```

Listeners are grouped by Phalcon event type, such as `dispatch`, `db`, `model`,
or `view`. A listener can be a class name, a DI service name, or an array with
`class` or `service`. Array definitions also support `priority`, `arguments`,
and `enabled => false`.

The bootstrap attaches these listeners after providers are registered and before
modules/router setup. Core providers keep their existing built-in listener
wiring; this config is for application listeners that should participate in the
same shared event manager without replacing providers.

## Stateless Identity

API-only applications can keep PhalconKit's normal `session` service available
while making identity itself stateless:

```php
'identity' => [
    'stateless' => true,
],
```

or through the environment:

```ini
IDENTITY_STATELESS=true
```

Stateless identity stores the small identity payload, such as `userId` and
`asUserId`, directly in the JWT claim instead of PHP session storage. The
session provider still registers and starts the configured Phalcon session
manager, so flash messages, OAuth2 state, locale persistence, and other session
consumers keep their normal behavior.

Do not combine stateless identity with `identity.sessionFallback`; fallback
storage is ignored when `identity.stateless` is enabled. Clients must replace
their JWT after login, logout, OAuth2 login, impersonation, and refresh
responses that include new token values. Old JWTs are not server-revoked unless
the application adds its own revocation strategy.

### App Service Provider Example

Use a provider when the service belongs in the DI container and is shared by
controllers, tasks, models, or other services.

```php
<?php

namespace App\Provider\Report;

use App\Service\ReportExporter;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

final class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'reportExporter';

    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            return new ReportExporter(
                $di->getTyped('db', \Phalcon\Db\Adapter\AdapterInterface::class),
                $di->getTyped('logger', \Phalcon\Logger\LoggerInterface::class)
            );
        });
    }
}
```

### PhalconKit DI Boundary

Applications that use `PhalconKit\Bootstrap` normally do not need to change
anything: bootstrap creates a PhalconKit DI container before registering config,
providers, modules, and services.

Custom bootstraps and tests that pass their own container into
`Bootstrap::setDI()` must pass `PhalconKit\Di\DiInterface`, such as
`PhalconKit\Di\Di`, `PhalconKit\Di\FactoryDefault`, or
`PhalconKit\Di\FactoryDefault\Cli`. Native `Phalcon\Di\Di` is no longer the
provider/bootstrap boundary because it does not expose PhalconKit's typed
helpers.

Use the typed helpers when the service contract is known:

```php
$config = $di->getConfig();
$view = $di->getTyped('view', \Phalcon\Mvc\ViewInterface::class);
```

Native Phalcon DI signatures may still appear where PhalconKit extends native
Phalcon interfaces or classes. App-owned providers should use
`PhalconKit\Di\DiInterface`.

Register it in app config:

```php
'providers' => [
    \App\Provider\Report\ServiceProvider::class =>
        \App\Provider\Report\ServiceProvider::class,
],
```

Then use it from an injectable class:

```php
$this->reportExporter->exportProject($projectId);
```

## Model Aliases

Applications can map framework model roles to app model classes. This keeps
identity, permissions, and scaffolded resources decoupled from a fixed model
namespace.

```php
'models' => [
    'user' => \App\Models\User::class,
    'role' => \App\Models\Role::class,
    'workspace' => \App\Models\Workspace::class,
],
```

## Permissions

Permission config maps features to components, component methods, optional
query behaviors, and roles.

```php
'permissions' => [
    'features' => [
        'manageLocation' => [
            'components' => [
                \App\Modules\Api\Controllers\LocationController::class => ['*'],
                \App\Models\Location::class => ['*'],
            ],
        ],
    ],
    'roles' => [
        'admin' => [
            'features' => ['manageLocation'],
        ],
    ],
],
```

The identity/security system can enforce permissions across controllers,
actions, models, methods, CLI tasks, and WebSocket tasks.

For row-level controller conditions and role inheritance, read
[Identity And Permissions](identity-and-permissions.md).
