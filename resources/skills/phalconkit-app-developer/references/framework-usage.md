# PhalconKit Framework Usage

Use this reference when an app task needs concrete PhalconKit conventions. Start
from the app's existing code when it exists; use these patterns when the app is
new, sparse, or inconsistent.

## Phalcon Baseline

Native Phalcon references:

- MVC overview: https://docs.phalcon.io/5.17/mvc/
- Application API: https://docs.phalcon.io/5.17/api/phalcon_mvc/#mvcapplication
- Dependency injection: https://docs.phalcon.io/5.17/di/
- Loader/autoloading: https://docs.phalcon.io/5.17/autoload/
- Controllers: https://docs.phalcon.io/5.17/controllers/
- CLI applications: https://docs.phalcon.io/5.17/cli/

PhalconKit applications keep Phalcon MVC, DI, loader, controller, and CLI
semantics, then add convention-driven bootstrap, modules, providers, REST
controllers, models, tasks, and config composition.

## Application Structure

Real PhalconKit apps are usually organized around a small app bootstrap, central
config, generated model layers, and module-specific controllers/tasks/views.
Use the existing app tree first; for new apps, this structure is a strong
baseline:

```text
app/
  Bootstrap.php
  index.php
  Config/
    Config.php
    Exposers.php
    Jsons/
      .gitkeep
      service-account.example.json
    Permissions/
      EventConfig.php
      UserConfig.php
      ...
  Enums/
    File/
      AllowedExtensions.php
      AllowedMimeTypes.php
      FileCategories.php
  Models/
    AbstractModel.php
    Abstracts/
      EventAbstract.php
      Interfaces/
        EventAbstractInterface.php
    Interfaces/
      EventInterface.php
    Enums/
      EventStatus.php
      ResolutionStatus.php
    Event.php
    User.php
    ...
  Modules/
    Api/
      Behaviors/
      Controllers/
        AbstractController.php
        EventController.php
      Transformers/
        AbstractModelTransformer.php
        EventTransformer.php
      Module.php
    Cli/
      Tasks/
        AbstractTask.php
        CronTask.php
      Module.php
    Frontend/
      Controllers/
      Views/
      Module.php
    Ws/
      Tasks/
      Module.php
  Provider/
    Firebase/
      ServiceProvider.php
    Identity/
      IdentityManager.php
      ServiceProvider.php
```

Structure rules:

- `app/Bootstrap.php` installs `App\Config\Config` before PhalconKit registers
  services and modules.
- `Config/Config.php` owns app-level overrides for modules, providers, models,
  routes, services, and app settings.
- `Config/Permissions/*Config.php` keeps per-resource permission definitions
  out of controllers.
- `Config/Exposers.php` centralizes API response shapes used by REST
  controllers and nested relations.
- `Config/Jsons/` may hold local service-account JSON files, but real secrets
  must stay out of public examples and should be ignored unless the deployment
  policy explicitly says otherwise.
- `Models/Abstracts/` and `Models/Abstracts/Interfaces/` are generated or
  scaffold-owned base layers. Avoid hand-editing them unless the app process
  says they are maintained manually.
- `Models/Interfaces/` and concrete `Models/*.php` are the app-facing model
  contracts and behavior.
- `Models/Enums/` holds model/domain enums; `app/Enums/` is useful for broader
  app or integration enums that are not tied to one model layer.
- `Modules/Api/Behaviors/` stores reusable REST controller behaviors.
- `Modules/Api/Transformers/` stores Fractal transformers when the app uses
  transformer-backed API responses. See `transformers.md`.
- `Provider/` stores app-owned providers and core provider overrides. Register
  them through config using the patterns in `providers.md`.

For a deeper root config and permission config recipe, read
`configuration.md`.

## Bootstrap

PhalconKit applications usually load Composer and the app namespace, then run
`PhalconKit\Bootstrap`.

```php
use Phalcon\Autoload\Loader;
use PhalconKit\Bootstrap;

$loader = new Loader();
$loader->setFiles(['vendor/autoload.php']);
$loader->setNamespaces(['App' => APP_PATH]);
$loader->register();

echo (new Bootstrap())->run();
```

`Bootstrap` decides the mode (`mvc`, `cli`, or `ws`), creates the default DI,
registers config, registers service providers, boots providers, registers
modules, and registers the router. To customize early wiring, prefer an app
bootstrap subclass that overrides `initialize()` before services are
registered.

```php
final class AppBootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new \App\Config\Config());
    }
}
```

Real apps usually keep this class in the app namespace so entrypoints can run
`new \App\Bootstrap()` while the custom config is installed before providers,
modules, and routes are registered.

```php
namespace App;

use App\Config\Config;

class Bootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new Config());
    }
}
```

Common root entrypoints keep autoloading in one `loader.php`, then run the
same app bootstrap in different modes:

```php
<?php
// loader.php
use Phalcon\Autoload\Loader;

const APP_NAMESPACE = 'App';
const ROOT_PATH = __DIR__ . '/';
const VENDOR_PATH = ROOT_PATH . 'vendor/';
const APP_PATH = ROOT_PATH . 'app/';

$loader = new Loader();
$loader->setFiles([VENDOR_PATH . 'autoload.php']);
$loader->setNamespaces([APP_NAMESPACE => APP_PATH]);
$loader->setFileCheckingCallback(null);
$loader->register();

return $loader;
```

```php
<?php
// index.php
use App\Bootstrap;

$loader = require 'loader.php';
echo new Bootstrap()->run();
```

```php
<?php
// public/index.php
require '../index.php';
```

```php
#!/usr/bin/env php
<?php
// cli
use App\Bootstrap;

$loader = require 'loader.php';
echo new Bootstrap('cli')->run();
```

```php
#!/usr/bin/env php
<?php
// websocket
use App\Bootstrap;

$loader = require 'loader.php';
echo (new Bootstrap('ws'))->run();
```

Entrypoint rules:

- Keep constants such as `ROOT_PATH`, `VENDOR_PATH`, and `APP_PATH` in the
  loader so every runtime resolves the same app tree.
- Keep `public/index.php` thin. It should point web traffic at the root MVC
  entrypoint without duplicating bootstrap logic.
- Use `new Bootstrap('cli')` for CLI tasks and `new Bootstrap('ws')` for
  Swoole/WebSocket tasks.
- Keep executable entrypoints extensionless if that is the app convention, but
  make sure deployment and process manager commands point at the real filename.

## Config

App config should extend or instantiate `PhalconKit\Bootstrap\Config`. Core
defaults are defined there and constructor input is append-merged into those
defaults. For associative keys, app values replace default values with the same
key. For numeric lists, app values are appended.

Use a custom bootstrap to install the app config before the config service is
registered:

```php
final class AppBootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        $this->setConfig(new \App\Config\Config());
    }
}
```

Then pass overrides from the app config constructor:

```php
final class Config extends \PhalconKit\Bootstrap\Config
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        parent::__construct([
            'app' => [
                'name' => \PhalconKit\Support\Env::get('APP_NAME', 'My App'),
            ],
            'modules' => [
                'backoffice' => [
                    'className' => \App\Modules\Backoffice\Module::class,
                    'path' => APP_PATH . 'Modules/Backoffice/Module.php',
                ],
            ],
        ], $insensitive);
    }
}
```

Use `$config->pathToArray('section.path')` for nested config that may be a
Phalcon config object. Keep secrets in `.env`; do not hard-code keys,
passwords, tokens, or DSNs.

Config drives service providers. A provider is a pre-configured DI service: it
reads its config section, constructs the service, registers it under a stable
service name, and makes it available to controllers/components through DI. For
the complete provider lifecycle, override rules, and provider catalog, read
`providers.md`. For app-level module, router, model, provider, locale,
integration, and permission examples, read `configuration.md`.

Common app override points:

```php
parent::__construct([
    'app' => [
        'name' => \PhalconKit\Support\Env::get('APP_NAME', 'My App'),
    ],
    'url' => [
        'baseUri' => \PhalconKit\Support\Env::get('URL_BASE_URI', '/'),
    ],
    'modules' => [
        'api' => [
            'className' => \App\Modules\Api\Module::class,
            'path' => APP_PATH . 'Modules/Api/Module.php',
        ],
    ],
    'providers' => [
        \PhalconKit\Provider\Response\ServiceProvider::class =>
            \App\Provider\Response\ServiceProvider::class,
    ],
], $insensitive);
```

After `parent::__construct()`, the config object is mutable. If an app must
remove a default provider completely, remove it from the nested `providers`
config object after the parent constructor. Do not set a provider value to
`false`; `Bootstrap::registerServices()` expects every provider value to be a
class-string.

```php
final class Config extends \PhalconKit\Bootstrap\Config
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        parent::__construct($data, $insensitive);

        $this->providers->remove(\PhalconKit\Provider\OpenAi\ServiceProvider::class);
    }
}
```

## Service Providers

Service providers are the normal extension point for shared services. Extend
`PhalconKit\Provider\AbstractServiceProvider`, set `$serviceName`, and register
a shared service in DI.

The provider is not the service itself; it is the factory/configuration layer
that injects the ready-to-use service into DI. Application code should normally
consume the service (`$this->response`, `$this->db`, `$this->identity`,
`$this->di->get('openAi')`) rather than instantiating the provider class.

```php
use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

final class SearchServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'search';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            return new SearchClient($config->pathToArray('search') ?? []);
        });
    }
}
```

Register providers through config:

```php
'providers' => [
    \App\Provider\SearchServiceProvider::class => \App\Provider\SearchServiceProvider::class,
],
```

Provider config is an expected-provider to actual-provider map:

```php
'providers' => [
    ExpectedProvider::class => ActualProvider::class,
],
```

PhalconKit registers the actual class values in order. The key is still
important because append-merge replacement happens by key. To replace a core
provider, use the core provider class as the key and the app provider class as
the value. Do not add the replacement under a new key unless the app really
wants both providers registered.

```php
'providers' => [
    \PhalconKit\Provider\Response\ServiceProvider::class =>
        \App\Provider\Response\ServiceProvider::class,
],
```

The replacement provider should preserve the DI service name expected by the
rest of the framework. For example, a response provider replacement should still
use `$serviceName = 'response'`.

```php
namespace App\Provider\Response;

use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Http\Response;
use PhalconKit\Provider\AbstractServiceProvider;

final class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'response';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            $response = new Response();
            $response->setDI($di);
            $response->setHeader('X-App-Response', 'custom');

            foreach ($config->pathToArray('response.headers') ?? [] as $name => $value) {
                $response->setHeader($name, $value);
            }

            return $response;
        });
    }
}
```

Provider replacement checklist:

- Keep the same `$serviceName` unless every consumer is updated.
- Return a compatible service type for existing code and DI docblocks.
- Read options from config, not directly from scattered environment variables.
- Register the replacement with the core provider class as the config key.
- Add or adjust tests around the DI service, not just the provider class.

## Modules And Routing

MVC modules extend `PhalconKit\Mvc\Module`; CLI modules extend
`PhalconKit\Cli\Module`; WebSocket modules extend `PhalconKit\Ws\Module`. The
module class namespace determines conventional sub-namespaces:

- `<ModuleNamespace>\Controllers`
- `<ModuleNamespace>\Models`
- `<ModuleNamespace>\Transformers`
- `<ModuleNamespace>\Tasks` for CLI
- `<ModuleNamespace>\Tasks` for WebSocket modules
- `<ModuleDir>/Views/` for MVC views

```php
namespace App\Modules\Backoffice;

final class Module extends \PhalconKit\Mvc\Module
{
    public string $name = 'backoffice';
}
```

For real apps, prefer thin module wrappers around the PhalconKit pre-defined
modules. Override `getNamespaces()` when the module needs shared app model
classes or other app namespaces in addition to the parent module namespaces.
Put app namespaces before `parent::getNamespaces()` and keep the parent call so
core module controllers, tasks, transformers, and `PhalconKit\Models` remain
available.

```php
namespace App\Modules\Api;

class Module extends \PhalconKit\Modules\Api\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

Use the same wrapper pattern for CLI, WebSocket, and Frontend modules when they
need the shared app model namespace:

```php
namespace App\Modules\Cli;

class Module extends \PhalconKit\Modules\Cli\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

```php
namespace App\Modules\Ws;

class Module extends \PhalconKit\Modules\Ws\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

```php
namespace App\Modules\Frontend;

class Module extends \PhalconKit\Modules\Frontend\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

Add the module to config:

```php
'modules' => [
    'backoffice' => [
        'className' => \App\Modules\Backoffice\Module::class,
        'path' => APP_PATH . 'Modules/Backoffice/Module.php',
    ],
],
```

The core router mounts default, localized, hostname, and module routes from
config. Prefer module registration and `router.hostnames` config before adding a
custom router provider.

## Controllers

Plain MVC controllers can extend `PhalconKit\Mvc\Controller`. REST controllers
usually extend the module base controller; for the API module that is
`PhalconKit\Modules\Api\Controller`, which extends the framework RESTful
controller.

Use `PhalconKit\Mvc\Controller\Rest` for custom JSON endpoints that need
request params, debug/fractal helpers, controller behaviors, and
`setRestResponse()`, but do not need model-backed REST actions. Use
`PhalconKit\Mvc\Controller\Restful` for model-backed resources that need the
standard find/save/delete/export/count/sum actions, field policies, model
lookup, query compilation, expose rules, eager loading, joins, and permission
conditions. The API module base controller already extends `Restful`. For usage
examples, read `rest-api-controllers.md`.

RESTful controllers infer the model from the controller name and registered
model namespaces. `InvoiceController` should have an `Invoice` model in one of
the module/app model namespaces.

```php
use PhalconKit\Modules\Api\Controller;

final class InvoiceController extends Controller
{
    public function initializeSearchFields(): void
    {
        $this->setSearchFields([
            'id',
            'number',
            'customerName',
        ]);
    }

    public function initializeExposeFields(): void
    {
        $this->setExposeFields([
            true,
            'internalNotes' => false,
        ]);
    }
}
```

Use `initializeWith()` for default eager-loaded relationship aliases, and use
expose/filter/search/save fields to constrain API behavior instead of filtering
output after the response is built. For realistic controller recipes with
relation graphs, exposers, joins, and permission conditions, read
`rest-api-controllers.md`.

Use app-level abstract controllers to centralize module behavior and leave
resource controllers focused on field policies, relations, and permissions.

```php
namespace App\Modules\Frontend\Controllers;

use PhalconKit\Modules\Frontend\Controller;

abstract class AbstractController extends Controller
{
}
```

## Error Controllers, Tasks, And SPA Fallbacks

Keep app-level wrappers for module error controllers and tasks even when they
are empty. They make router defaults resolve inside the app namespace and leave
a stable place for future app-specific error behavior.

```php
namespace App\Modules\Api\Controllers;

class ErrorController extends \PhalconKit\Modules\Api\Controllers\ErrorController
{
}
```

```php
namespace App\Modules\Cli\Tasks;

class ErrorTask extends \PhalconKit\Modules\Cli\Tasks\ErrorTask
{
}
```

```php
namespace App\Modules\Ws\Tasks;

class ErrorTask extends \PhalconKit\Modules\Ws\Tasks\ErrorTask
{
}
```

For SPA frontends, the frontend error controller may intentionally turn
not-found routes into a `200` and forward to the SPA host controller. This lets
the frontend router handle client-side routes that the PHP router does not know.

```php
namespace App\Modules\Frontend\Controllers;

class ErrorController extends \PhalconKit\Modules\Frontend\Controllers\ErrorController
{
    public function notFoundAction(): void
    {
        $this->setStatusCode(200);

        if ($this->dispatcher->getPreviousControllerName() === 'admin') {
            $this->dispatcher->forward([
                'controller' => 'admin',
                'action' => 'index',
            ], true);
            return;
        }

        $this->dispatcher->forward([
            'controller' => 'index',
            'action' => 'index',
        ]);
    }
}
```

A matching frontend view can include the built SPA file and return a clear
server error when the compiled asset is missing:

```php
<?php
$path = $this->dispatcher->getControllerName() === 'admin' ? '/admin' : '/dist';
$frontend = $this->config->app->dir->public . $path . '/index.html';

if (file_exists($frontend)) {
    include_once $frontend;
} else {
    $response = $this->response;
    $response->setStatusCode(404, 'Frontend not found');

    return $response;
}
```

Use this SPA fallback only for frontend modules. API, CLI, and WebSocket error
handlers should keep real error status behavior.

## Models And Migrations

PhalconKit models usually have a generated abstract class and interface plus a
small concrete class for hand-written behavior:

- `Models/Abstracts/InvoiceAbstract.php` owns generated properties,
  `columnMap()`, default relationships, and default validations.
- `Models/Interfaces/InvoiceInterface.php` extends the abstract interface.
- `Models/Invoice.php` extends the abstract and implements the interface.

Concrete models should keep custom behavior small:

```php
final class Invoice extends InvoiceAbstract implements InvoiceInterface
{
    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();
    }

    public function validation(): bool
    {
        $validator = $this->genericValidation();
        $this->addDefaultValidations($validator);
        return $this->validate($validator);
    }
}
```

Do not hand-edit generated abstracts unless the project process says those files
are hand-maintained. When schema changes are involved, update migrations and
regenerate generated model layers through the app's established tooling. For
migration helper scripts, scaffold modes, concrete model patterns, custom
relationships, validation rules, model events, Redis/WebSocket publish hooks,
and model alias config, read `models-and-scaffolding.md`.

## CLI Tasks

CLI tasks extend the app's CLI task base or `PhalconKit\Modules\Cli\Task`.
Actions end in `Action`, and `$cliDoc` documents usage.

Apps commonly add a thin abstract task so shared CLI behavior can be added
later without changing every task class.

```php
namespace App\Modules\Cli\Tasks;

class AbstractTask extends \PhalconKit\Modules\Cli\Tasks\AbstractTask
{
}
```

```php
final class ReportTask extends AbstractTask
{
    public string $cliDoc = <<<DOC
Usage:
  phalcon-kit cli report daily

DOC;

    public function dailyAction(): bool
    {
        return true;
    }
}
```

Follow the app's command naming and router defaults before adding new CLI
wiring. For CLI permissions, output formatting, WebSocket tasks, Swoole
handlers, Redis pub/sub bridges, and live channel broadcasts, read
`cli-and-websocket.md`.

## Agent Workflow

For any framework task:

1. Find the nearest working example in the app.
2. Identify whether the change belongs in config, a provider, a module,
   controller, model, migration, or task.
3. Keep generated files and hand-written files separate.
4. Add tests around behavior changes using the app's existing test framework.
5. Run the smallest relevant validation commands.
