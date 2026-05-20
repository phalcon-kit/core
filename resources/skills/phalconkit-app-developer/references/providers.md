# PhalconKit Providers

Use this reference when a task touches PhalconKit services, DI registration,
provider overrides, or service usage from controllers, tasks, models,
components, and helpers.

For auth controller behavior, JWT/session flow, impersonation, ACL roles, and
permission policies, also read `references/identity-and-security.md`.

## Phalcon Baseline

Native Phalcon references:

- Dependency injection: https://docs.phalcon.io/5.13/di/
- Config service: https://docs.phalcon.io/5.13/config/
- Controllers and DI access: https://docs.phalcon.io/5.13/controllers/

PhalconKit providers are app-facing wrappers around native Phalcon DI service
registration. Use native Phalcon docs for raw DI behavior, shared services,
service providers, and config-backed service loading.

## Provider Model

A PhalconKit provider is a pre-configured service registration for the Phalcon
DI container. The provider reads the framework config, builds the service, and
registers it under a stable DI service name. After bootstrap, controllers and
components can use the service without manually constructing it.

The normal lifecycle is:

1. `Bootstrap` creates the DI and stores itself as `bootstrap`.
2. `Bootstrap::initialize()` runs; app bootstraps should install custom config
   here with `$this->setConfig(new \App\Config\Config())`.
3. `Bootstrap::registerConfig()` registers `config` first.
4. `Bootstrap::registerServices()` reads `$config->pathToArray('providers')`.
5. Each provider value must be a provider class-string. Bootstrap instantiates
   it and registers it with the DI.
6. The provider's `$serviceName` becomes the DI service name.
7. Framework controllers, components, tasks, and services retrieve the service
   from DI by that name.

Most providers extend `PhalconKit\Provider\AbstractServiceProvider`:

```php
use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

final class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'billing';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            return new BillingClient($config->pathToArray('billing') ?? []);
        });
    }
}
```

Prefer `setShared()` for stateful or expensive services. Use non-shared
registration only when the service is intentionally created each time it is
requested.

## Service Access

In PhalconKit controllers, tasks, components, and other injectable classes,
prefer the injected property when it is declared by the framework:

```php
public function profileAction(): void
{
    $identity = $this->identity->get();

    $this->response->setJsonContent([
        'identity' => $identity,
    ]);
}
```

The universal DI access patterns are:

```php
$response = $this->response;
$response = $this->di->get('response');
$response = $this->getDI()->get('response');
$response = \Phalcon\Di\Di::getDefault()->get('response');
```

Use `$this->di->get('serviceName')` when the service is not documented as an
injectable property or when working outside a PhalconKit injectable class.

## Provider Config And Overrides

The `providers` config is an expected-provider to actual-provider map:

```php
'providers' => [
    ExpectedProvider::class => ActualProvider::class,
],
```

`Bootstrap::registerServices()` registers the actual provider class values, but
the expected provider key is how append-merge replacement works. To override a
core provider, keep the core provider class as the key and put the app provider
class as the value:

```php
'providers' => [
    \PhalconKit\Provider\Response\ServiceProvider::class =>
        \App\Provider\Response\ServiceProvider::class,
],
```

The same map can include new app-owned providers. For new services, use the app
provider class as both the key and value:

```php
'providers' => [
    \PhalconKit\Provider\Identity\ServiceProvider::class =>
        \App\Provider\Identity\ServiceProvider::class,
    \App\Provider\Firebase\ServiceProvider::class =>
        \App\Provider\Firebase\ServiceProvider::class,
],
```

The replacement should preserve the same `$serviceName` and return a compatible
service. For example, a custom response provider should still register
`response`:

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

Do not set provider values to `false`; current bootstrap registration expects
class-string values. If an app must remove a default provider completely, remove
the key from the nested providers config object after the parent config
constructor has run.

## Core Provider Override Example

Use the core provider class as the config key when replacing a core service.
This example replaces PhalconKit's `identity` service with an app manager that
stores identity state in an app `Session` model instead of only the configured
session adapter.

```php
'providers' => [
    \PhalconKit\Provider\Identity\ServiceProvider::class =>
        \App\Provider\Identity\ServiceProvider::class,
],
```

The provider keeps `$serviceName = 'identity'` so existing controllers,
components, and tasks can keep using `$this->identity` or
`$this->di->get('identity')`.

```php
namespace App\Provider\Identity;

use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'identity';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            $options ??= $config->pathToArray('identity') ?? [];

            $identity = new IdentityManager($options);
            $identity->setDI($di);

            return $identity;
        });
    }
}
```

The app manager should extend the core manager when it only changes storage or
lookup behavior. That keeps login, logout, role, ACL, OAuth2, JWT, and user
helper behavior from `PhalconKit\Identity\Manager` while moving the session
identity record to an app model.

```php
namespace App\Provider\Identity;

use App\Models\Session;
use PhalconKit\Db\Column;
use PhalconKit\Identity\Manager;

class IdentityManager extends Manager
{
    protected array $cachedSession = [];

    public function removeSessionIdentity(): void
    {
        $key = $this->getKey();
        if (!$key) {
            return;
        }

        $session = $this->getSessionByKey($key);
        if ($session) {
            $session->delete();
            unset($this->cachedSession[$key]);
        }
    }

    public function setSessionIdentity(array $identity): void
    {
        $key = $this->getKey();
        if (!$key) {
            return;
        }

        $session = $this->getSessionByKey($key);
        if (!$session) {
            $session = new Session();
            $session->setKey($key);
        }

        $session->setAsUserId($identity['asUserId'] ?? null);
        $session->setUserId($identity['userId'] ?? null);

        if (!$session->save()) {
            throw new \Exception(
                'Unable to save session: ' . json_encode($session->getMessages()),
                400
            );
        }

        $this->cachedSession[$key] = $session;
    }

    public function getSessionIdentity(): array
    {
        $key = $this->getKey();
        if (!$key) {
            return [];
        }

        $session = $this->getSessionByKey($key);
        if (!$session) {
            return [];
        }

        return [
            'userId' => $session->getUserId(),
            'asUserId' => $session->getAsUserId(),
        ];
    }

    public function hasSessionIdentity(): bool
    {
        $key = $this->getKey();

        return $key && $this->getSessionByKey($key) instanceof Session;
    }

    public function getSessionByKey(string $key): ?Session
    {
        if (isset($this->cachedSession[$key])) {
            return $this->cachedSession[$key];
        }

        $session = Session::findFirst([
            'key = :key: and deleted <> :deleted:',
            'bind' => [
                'key' => $key,
                'deleted' => Column::YES,
            ],
            'bindTypes' => [
                'key' => Column::BIND_PARAM_STR,
                'deleted' => Column::BIND_PARAM_INT,
            ],
        ]);

        if ($session instanceof Session) {
            $this->cachedSession[$key] = $session;

            return $session;
        }

        return null;
    }
}
```

Override rules:

- Keep the original service name (`identity`) unless every consumer changes.
- Extend the core manager when the app only customizes one behavior area.
- Keep storage-specific details in the manager, not in controllers.
- Prefer a compatible app model contract: the session model should expose the
  key, user id, as-user id, deleted state, and validation behavior the manager
  expects.
- Test login, logout, impersonation, role checks, and missing/deleted session
  cases after changing identity storage.

## New App Provider Example

For a service PhalconKit does not ship, use the app provider class as both the
provider map key and value.

```php
'providers' => [
    \App\Provider\Firebase\ServiceProvider::class =>
        \App\Provider\Firebase\ServiceProvider::class,
],
'firebase' => [
    'jsonFile' => \PhalconKit\Support\Env::get('FIREBASE_JSON_FILE', ''),
    'databaseUri' => \PhalconKit\Support\Env::get('FIREBASE_DATABASE_URI', ''),
],
```

The provider reads the app config section and registers the ready-to-use
service under its `$serviceName`. For custom services not declared in
PhalconKit's injectable property docblocks, retrieve the service through DI or
add app-level docblocks on your app base controller/task.

```php
namespace App\Provider\Firebase;

use Kreait\Firebase\Factory;
use LogicException;
use Phalcon\Di\DiInterface;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'firebase';

    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
            $config = $di->get('config');
            assert($config instanceof ConfigInterface);

            $options ??= $config->pathToArray('firebase') ?? [];

            if (empty($options['jsonFile'])) {
                throw new LogicException('Missing firebase.jsonFile config.');
            }

            $factory = (new Factory())->withServiceAccount($options['jsonFile']);

            if (!empty($options['databaseUri'])) {
                $factory = $factory->withDatabaseUri($options['databaseUri']);
            }

            return $factory;
        });
    }
}
```

Usage:

```php
$firebase = $this->di->get('firebase');
$messaging = $firebase->createMessaging();
```

New provider rules:

- Keep third-party SDK dependencies in the app's `composer.json`.
- Put secret paths and URIs in config/env, not in the provider class.
- Register a clear config section with the same service purpose, such as
  `firebase` for the `firebase` service.
- Decide whether the service should return a factory, a client, or a narrow
  app wrapper. Controllers should usually call an app service instead of a raw
  SDK when business logic is involved.
- Add provider tests for DI retrieval and missing/invalid config.

## Default Provider Catalog

Provider class names below are under `PhalconKit\Provider\<Provider>\ServiceProvider`
unless a row says otherwise. The service name is the DI key.

### Bootstrap And Runtime

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Config` | `config` | All config sections | Registered before the provider map. Use `$this->config->path('app.name')` or `$this->config->pathToArray('database')`. Override by installing an app config in `Bootstrap::initialize()`, not by editing vendor defaults. |
| `Application` | `application` | `app`, `modules` | MVC application runtime used by `Bootstrap::run()`. Retrieve only for low-level bootstrapping: `$app = $this->di->get('application');`. |
| `Console` | `console` | `modules`, router CLI defaults | CLI runtime used in `cli` mode. Use task classes for normal CLI work; retrieve with `$this->di->get('console')` only for bootstrapping/tests. |
| `WebSocket` | `webSocket` | `modules`, router WS defaults | WebSocket application runtime used in `ws` mode. Use WebSocket tasks and `$this->di->get('webSocket')` for runtime integration. |
| `Debug` | `debug` | `app.debug`, `debug` | Enables PHP/debug behavior and optional MVC debug UI. Use `$this->debug` when inspecting debug state; keep sensitive config out of debug output. |
| `Env` | `env` | Environment | Wrapper for environment lookups. Prefer central config defaults over calling env directly in app services. |
| `Router` | `router` | `router`, `modules` | Mode-aware MVC/CLI/WS router. Use `$this->router` for route params and prefer config routes/module registration over ad hoc route wiring. |
| `Dispatcher` | `dispatcher` | `router`, `app`, events | Mode-aware dispatcher with PhalconKit security, maintenance, module, logging, REST, and error listeners. Controllers normally use it indirectly; use `$this->dispatcher->getActionName()` for action-aware controller setup. |
| `EventsManager` | `eventsManager` | none by default | Provider class exists, but the default provider map currently does not list it. Use `$this->di->get('eventsManager')` to attach listeners when the DI supplies it. Register this provider explicitly only when the app needs to replace event manager behavior. |
| `Request` | `request` | HTTP request state | HTTP request wrapper. Use `$this->request->getQuery()`, `$this->request->getPost()`, or REST controller helpers for request data. |
| `Response` | `response` | `response.headers`, `response.corsHeaders`, `response.cache` | HTTP response wrapper with configured headers. Use `$this->response->setJsonContent($payload)` or `setRestResponse()` in REST controllers. |

### Security And Identity

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Acl` | `acl` | `acl`, `permissions` | Builds the application access-control layer from permission config. Use `$this->acl` or identity helpers when checking role/resource/action access. |
| `Security` | `security` | `security` | Phalcon security service with configured password hashing options. Use `$this->security->hash($password)` and related verification helpers. |
| `Session` | `session` | `session` | Session manager with stream, memcached, redis, or noop adapter. Use `$this->session->get('key')` and `$this->session->set('key', $value)`. |
| `Cookies` | `cookies` | `cookies` | Response cookie bag with configured encryption/signing. Use `$this->cookies->set('name', 'value')` and `$this->cookies->get('name')`. |
| `Crypt` | `crypt` | `crypt` | Encryption service with configured cipher, key, signing, and padding. Use `$this->crypt->encrypt($value)` and `$this->crypt->decrypt($value)`. |
| `Filter` | `filter` | `filters` | Filter locator with optional app-defined filters. Use `$this->filter` for sanitization/validation filters instead of hand-written string cleanup. |
| `Jwt` | `jwt` | `security.jwt` | JWT helper for builder/parser/validator flow. Use `$token = $this->jwt->buildToken($this->jwt->builder([...]))` and `$this->jwt->validateToken($this->jwt->parseToken($raw))`. |
| `ReCaptcha` | `reCaptcha` | `reCaptcha` | Google reCAPTCHA verifier. Use `$this->reCaptcha->verify($token, $this->request->getClientAddress())` in form or API validation. |
| `Identity` | `identity` | `identity`, `models`, `security.jwt` | Auth/session identity manager. Use `$this->identity->get()`, `$this->identity->login($params)`, `$this->identity->logout()`, and role helpers such as `$this->identity->hasRole(['admin'])`. |

### Language

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Locale` | `locale` | `locale`, router/session/request state | Chooses the current locale from route/session/request depending on mode. Use `$this->locale->get()` or `$this->locale->setLocale('fr')`. |
| `Translate` | `translate` | `translate` | Gettext translator. Use `$this->translate->_('message-key')` or adapter-specific translation methods in views/controllers. |

### View And Frontend

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `View` | `view` | `view`, module view paths | MVC view service with configured engines. Controllers normally use it indirectly; use `$this->view->setVar('name', $value)` for view variables. |
| `Url` | `url` | `url`, router | URL generator with base/static URI config. Use `$this->url->get('path')` or `$this->url->getStatic('asset.css')`. |
| `Volt` | `volt` | `volt`, `view` | Volt template engine. Configure compile path/options through `volt`; use indirectly through the view engine map. |
| `Tag` | `tag` | DI escaper/url | HTML tag helper. Use `$this->tag` in views/helpers when generating framework-managed HTML tags. |
| `Assets` | `assets` | DI escaper/tag | Asset manager. Use `$this->assets->addCss(...)`, `$this->assets->addJs(...)`, and output collections in layouts. |
| `Flash` | `flash` | `flash` | Flash message service. Use `$this->flash->success('Saved')`, `$this->flash->error('Failed')`, or the session driver when configured. |
| `Escaper` | `escaper` | none | HTML escaper. Use `$this->escaper->escapeHtml($value)` or view helpers when outputting user-controlled data. |

### Database, Models, And Cache

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Database` | `db` | `database.default`, `database.drivers` | Primary database adapter. Models use it automatically; use `$this->db->fetchAll($sql, \Phalcon\Db\Enum::FETCH_ASSOC, $bind)` for explicit queries. |
| `DatabaseReadOnly` | `dbr` | `database.drivers.readonly` | Read-only database adapter that extends the database provider. Use `$this->dbr` for read-heavy queries when a read replica is configured. |
| `DatabaseDynamic` | `dbd` | `database.drivers.dynamic` | Dynamic database adapter that extends the database provider. Use `$this->dbd` when an app switches to a separate dynamic database connection. |
| `ModelsManager` | `modelsManager` | model metadata/events | PhalconKit model manager. Use indirectly through models; retrieve for advanced relationship/query setup. |
| `Models` | `models` | `models` | Core-to-app model class map. Use `$this->models->getInstance(\PhalconKit\Models\User::class)` or generated helpers such as `$this->models->getUser()` when resolving overridden model classes. |
| `Profiler` | `profiler` | database profiler/loggers | Database profiler used by database events. Use `$this->profiler` when collecting or inspecting query profile data. |
| `Annotations` | `annotations` | `annotations` | Annotation adapter using memory, apcu, or stream drivers. Use `$this->annotations` for annotation reflection when a feature depends on annotations. |
| `ModelsMetadata` | `modelsMetadata` | `metadata` | Model metadata adapter using memory, apcu, stream, memcached, or redis. Models use it automatically. |
| `ModelsCache` | `modelsCache` | `cache` | Cache service dedicated to model caching, implemented with the same cache provider behavior. Use from models or repositories when caching model data. |
| `Cache` | `cache` | `cache` | General cache service using memory, apcu, stream, memcached, or redis. Use `$this->cache->get($key)` and `$this->cache->set($key, $value)`. |
| `Redis` | `redis` | `redis` | Native Redis client connection. Use `$this->redis` for Redis-specific operations not covered by cache/session adapters. |

### Logging

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Loggers` | `loggers` | `logger`, `loggers` | Named logger collection. Use `$this->loggers->get('request')` or the configured names for domain-specific logs. |
| `Logger` | `logger` | `logger.default` | Default logger selected from the logger collection. Use `$this->logger->info('message')`, `$this->logger->error('message')`, etc. |

### Mail, Storage, And External Integrations

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Mailer` | `mailer` | `mailer` | Incubator mailer manager with mail/sendmail/smtp drivers. Use `$this->mailer` for application emails and keep recipients/sender defaults in config. |
| `Imap` | `imap` | `imap` | IMAP mailbox client. Use `$this->imap` to read mailbox messages and attachments. |
| `FileSystem` | `fileSystem` | `fileSystem`, `app.dir.root` | Flysystem filesystem. Current provider registers a local filesystem rooted at config/app root; use `$this->fileSystem->read($path)` and `$this->fileSystem->write($path, $contents)`. |
| `Aws` | `aws` | `aws` | AWS SDK. Use `$this->aws->createS3()` or other SDK factory methods after credentials/region are configured. |
| `OCR` | `ocr` | binary/environment | Tesseract OCR wrapper. Use `$this->ocr->image($path)->run()` when OCR dependencies are installed on the host. |
| `Clamav` | `clamav` | `clamav` | ClamAV client over the configured socket/address. Use `$this->clamav` to scan uploaded files before processing them. |
| `OpenAi` | `openAi` | `openai` | OpenAI PHP client. Current provider reads `openai.apiKey`, `organization`, `project`, and `baseUri`; align app config before use. Use `$this->openAi` for API calls from services, not directly from controllers when domain logic belongs elsewhere. |
| `Oauth2Client` | `oauth2Client` | `oauth2.client` | Generic OAuth2 client provider. Use `$this->oauth2Client` for custom OAuth2 authorize/token/resource-owner flows. |
| `Oauth2Facebook` | `oauth2Facebook` | `oauth2.facebook` | Facebook OAuth2 provider. Use `$this->oauth2Facebook` for Facebook login/link flows. |
| `Oauth2Google` | `oauth2Google` | `oauth2.google` | Google OAuth2 provider. Use `$this->oauth2Google` for Google login/link flows. |
| `Swoole` | `swoole` | `swoole` | Swoole WebSocket server. Use `$this->swoole` from WS bootstrapping/tasks when the Swoole extension is installed. |

### Helpers And Utilities

| Provider | Service | Config | Usage |
| --- | --- | --- | --- |
| `Version` | `version` | package metadata | Framework version helper. Use `$this->version->get()` when reporting package/runtime versions. |
| `Helper` | `helper` | `helpers` | Helper factory with optional app-defined helpers. Use `$this->helper->slugify($value)` and other registered helper methods instead of duplicating helper code. |
| `Utils` | `utils` | none | General framework utility helper. Use `\PhalconKit\Support\Utils::getMemoryUsage()` or the DI service when an injectable needs the utility object. |
| `LoremIpsum` | `loremIpsum` | none | Lorem ipsum generator for scaffolds/dev fixtures. Use `$this->loremIpsum->words(10)` or package methods in non-production content generation. |
| `Gravatar` | `gravatar` | `gravatar` | Provider class is currently a placeholder: its service registration is commented out. Do not rely on `$this->gravatar` unless the app/core implements the registration. |

## Provider Replacement Checklist

- Keep the core provider class as the `providers` config key.
- Preserve `$serviceName` unless every service consumer is updated.
- Return a compatible object for the existing service contract.
- Read runtime options from config sections, not scattered env calls.
- Keep secrets in `.env` and expose only non-secret client config.
- Check `src/Di/InjectableProperties.php` when changing service names/types.
- Add tests around DI retrieval and real controller/component usage when
  runtime behavior changes.

For public documentation examples, cross-check the official service docs at
`https://phalcon-kit.github.io/docs/services/` and the generated/current docs
repo service pages when they are available locally.
