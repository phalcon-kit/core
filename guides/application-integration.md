# Add Core To An Existing Application

Use the [App skeleton](https://github.com/phalcon-kit/app) as the working reference
for entrypoints and modules. If your project already has a layout, integrate the
same contracts without moving application behavior into `vendor/`.

```shell
composer require phalcon-kit/core
composer check-platform-reqs
```

## Bootstrap Paths

A root `bootstrap.php` supplies paths before constructing Core configuration:

```php
<?php

declare(strict_types=1);

define('ROOT_PATH', __DIR__ . '/');
define('APP_PATH', ROOT_PATH . 'src/');
define('PUBLIC_PATH', ROOT_PATH . 'public/');
define('RESOURCES_PATH', ROOT_PATH . 'resources/');
define('STORAGE_PATH', ROOT_PATH . 'storage/');
define('VENDOR_PATH', ROOT_PATH . 'vendor/');

require VENDOR_PATH . 'autoload.php';
```

Register `App\\` as Composer's PSR-4 namespace for `src/`, then run
`composer dump-autoload`. Reuse the skeleton's `App\Config`, module classes,
and `App\Bootstrap` as a coherent set. Register your controller/task classes in
the corresponding namespace and configure their permissions explicitly.

## Select The Runtime

`src/Bootstrap.php`:

```php
<?php

declare(strict_types=1);

namespace App;

class Bootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new Config());
    }
}
```

`public/index.php`:

```php
<?php
require dirname(__DIR__) . '/bootstrap.php';
echo (new \App\Bootstrap(\PhalconKit\Bootstrap::MODE_MVC))->run();
```

`bin/phalcon-kit`:

```php
#!/usr/bin/env php
<?php
require dirname(__DIR__) . '/bootstrap.php';
echo (new \App\Bootstrap(\PhalconKit\Bootstrap::MODE_CLI))->run();
```

Explicit modes matter when starting HTTP handlers from CLI workers or tests.
Use `MODE_WS` for the optional WebSocket runtime after installing Swoole.

## Integrate In Small Steps

1. Register one application module and an index/health controller with an explicit
   public grant. Verify routing without a database.
2. Configure the database and run an application-owned migration.
3. Add an application base model extending `PhalconKit\Models\AbstractModel`.
4. Generate one table's model using [Scaffolding](database-scaffolding.md).
5. Add the [Project-style REST policies](first-rest-resource.md) and both controller
   and model grants.
6. Add identity only when its models, storage, keys, and application workflow are ready.

The module's namespace determines where controllers/tasks are resolved. Extending
a Core module under `App\Modules\Cli` does not automatically publish every Core
task under that namespace. Register an inherited application task and its CLI
permission, as shown in [CLI Tasks](cli-tasks.md).

## Using Your Own DI Container

Core components expect the PhalconKit DI contracts, including typed service
resolution. Start with its bootstrap/default containers unless you have a reason
to assemble services manually. Register compatible `config`, `helper`, `models`,
`modelsManager`, `modelsMetadata`, `modelsCache`, `security`, and `db` services
before using Core models. Identity-aware behavior also needs `identity` and its
request/session/JWT dependencies.

Use app providers for business services. Keep model aliases stable for the
container lifetime; aliases do not rewrite direct static model calls or previously
initialized ORM metadata. See [Configuration](configuration.md) and
[Feature Setup](feature-contracts.md).

## Acceptance Checks

Verify one HTTP read, one permitted write, one denied write, CLI execution from
another working directory, model save/reload, and your deployment's error page.
The generic `/api` health response proves bootstrap, not database or identity
readiness. Add application checks for those features separately.
