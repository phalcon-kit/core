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

## Environment

Example `.env` values:

```ini
APP_NAME="My App"

DATABASE_HOST=127.0.0.1
DATABASE_DBNAME=app
DATABASE_USERNAME=app
DATABASE_PASSWORD=app
```

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
