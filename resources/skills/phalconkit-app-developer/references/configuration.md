# PhalconKit Configuration

Use this reference when adding or reviewing app config, permission config,
module registration, model aliases, provider overrides, router defaults,
locale settings, or custom integration config.

## Phalcon Baseline

Native Phalcon references:

- Config service: https://docs.phalcon.io/5.17/config/
- Dependency injection: https://docs.phalcon.io/5.17/di/
- Routing config concepts: https://docs.phalcon.io/5.17/routing/
- Loader/autoloading: https://docs.phalcon.io/5.17/autoload/

PhalconKit config extends native Phalcon config and DI patterns. Use native docs
for raw config objects, DI service loading, and router behavior; use this file
for PhalconKit merge order, provider maps, permission fragments, and app module
registration.

## Merge Model

PhalconKit config classes extend `PhalconKit\Config\Config` or
`PhalconKit\Bootstrap\Config`.

`internalMergeAppend($source, $target)` merges recursively:

- associative keys from `$target` replace the same keys in `$source`
- nested associative arrays merge recursively
- numeric array entries from `$target` append to `$source`

Order matters. Build defaults first, then merge app overrides or resource
configs in the order that should win.

```php
$data = $this->internalMergeAppend($defaults, $data);
$data = $this->internalMergeAppend($data, new EventConfig()->toArray());

parent::__construct($data, $insensitive);
```

In the common app pattern, root defaults and incoming constructor data are
merged first, then resource permission config files are merged as canonical app
permissions. If a test or environment-specific override must win over those
resource configs, merge that override after the resource config chain.

Use `PhalconKit\Bootstrap\Config` for the root application config. Use
`PhalconKit\Config\Config` for smaller composed config fragments such as
`Config/Permissions/EventConfig.php`.

## Permission Config Files

Keep per-resource permissions in `app/Config/Permissions/*Config.php`. This
keeps ACL, role feature grants, and behavior attachment out of controllers.

```php
namespace App\Config\Permissions;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Modules\Api\Controllers\EventController;
use App\Modules\Api\Controllers\EventParticipantController;
use PhalconKit\Config\Config as PhalconKitConfig;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultPermissionCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultSoftDeleteConditionWhileFiltering;

class EventConfig extends PhalconKitConfig
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'manageEvent' => [
                        'components' => [
                            EventController::class => ['*'],
                            EventParticipantController::class => ['*'],
                            Event::class => ['*'],
                            EventParticipant::class => ['*'],
                        ],
                        'behaviors' => [
                            EventController::class => [
                                RemoveDefaultPermissionCondition::class,
                                RemoveDefaultSoftDeleteConditionWhileFiltering::class,
                            ],
                        ],
                    ],
                    'viewEvent' => [
                        'components' => [
                            EventController::class => [
                                'find',
                                'find-with',
                                'find-first',
                                'find-first-with',
                            ],
                            Event::class => ['find', 'findFirst'],
                            EventParticipant::class => ['find', 'findFirst'],
                        ],
                        'behaviors' => [
                            EventController::class => [
                                RemoveDefaultPermissionCondition::class,
                            ],
                        ],
                    ],
                ],
                'roles' => [
                    'admin' => [
                        'features' => [
                            'manageEvent',
                        ],
                    ],
                    'participant' => [
                        'features' => [
                            'viewEvent',
                        ],
                    ],
                    'guest' => [
                        'features' => [
                            'viewEvent',
                        ],
                    ],
                ],
            ],
        ], $data);

        parent::__construct($data, $insensitive);
    }
}
```

Permission config conventions:

- `permissions.features.<feature>.components` grants component/action access.
  Components can be controller classes, model classes, CLI task classes, or
  WebSocket task classes.
- `['*']` grants all actions on that component.
- REST routes use action names such as `find`, `find-with`,
  `find-first`, and `find-first-with`.
- Model access uses operation names such as `find`, `findFirst`, `create`,
  `update`, and `save`. Some apps mirror route-style names such as
  `find-first`; follow the local config convention.
- `permissions.roles.<role>.features` assigns reusable feature bundles to
  roles.
- `permissions.roles.<role>.inherit` lets identity role checks include another
  role's permissions.
- `permissions.features.<feature>.behaviors` attaches controller/model
  behaviors when the current identity has a role with that feature.
- Use behavior overrides carefully. Removing default permission or soft-delete
  conditions changes row visibility for that role/action.
- For custom behavior classes and the REST lifecycle hooks they can target, see
  `behaviors.md`.

Common behavior examples:

```php
RemoveDefaultPermissionCondition::class
RemoveDefaultSoftDeleteCondition::class
RemoveDefaultSoftDeleteConditionWhileFiltering::class
```

Attach behavior classes to the controller or model class they should affect.
Controller behavior attachment runs from the framework behavior trait before
the REST action executes.

Common feature patterns:

- `manage<Resource>`: grant `['*']` on the resource controller and related
  models, usually for `admin`.
- `view<Resource>`: grant read-only controller actions such as `find`,
  `find-with`, `find-first`, and `find-first-with`, plus read-only model
  methods.
- `create<Resource>` or `submit<Resource>`: grant only the controller save/read
  actions and model `create`/`update` methods needed by a participant workflow.
- Background features: grant model methods and task access to context roles
  such as `cli` for notification senders.
- WebSocket features: grant read/update model methods to the `ws` role when a
  Swoole task needs to query snapshots or update timers/statuses.
- Behavior entries are role-specific. An admin manage feature can remove
  default permission and soft-delete filtering while a participant view feature
  should usually stay narrower.

Compact examples:

```php
'manageLocation' => [
    'components' => [
        LocationController::class => ['*'],
        Location::class => ['*'],
    ],
    'behaviors' => [
        LocationController::class => [
            RemoveDefaultPermissionCondition::class,
            RemoveDefaultSoftDeleteConditionWhileFiltering::class,
        ],
    ],
],
'viewLocation' => [
    'components' => [
        LocationController::class => ['find', 'find-with'],
        Location::class => ['find'],
    ],
    'behaviors' => [
        LocationController::class => [
            RemoveDefaultPermissionCondition::class,
        ],
    ],
],
```

```php
'sendUserNotification' => [
    'components' => [
        Notification::class => ['find', 'findFirst', 'update'],
        UserNotification::class => ['find', 'findFirst', 'create', 'update'],
        UserFcm::class => ['find', 'findFirst', 'update'],
        FcmTask::class => ['*'],
    ],
],
```

```php
'createVoteSubmission' => [
    'components' => [
        VoteSubmissionController::class => [
            'find',
            'find-first',
            'find-with',
            'find-first-with',
            'save',
        ],
        VoteSubmission::class => ['create', 'update', 'find', 'findFirst'],
        VoteAnswer::class => ['create', 'update', 'find', 'findFirst'],
    ],
],
```

## Root App Config

The root app config composes the application runtime: modules, router defaults,
locale, provider overrides, model aliases, integration config, base roles, and
per-resource permission configs.

```php
namespace App\Config;

use App\Config\Permissions\EventConfig;
use App\Modules\Cli\Tasks\CronTask;
use App\Modules\Cli\Tasks\DatabaseTask;
use App\Modules\Frontend\Controllers\AdminController;
use App\Modules\Frontend\Controllers\ErrorController;
use App\Modules\Frontend\Controllers\IndexController;
use App\Modules\Ws\Module as WebSocketModule;
use App\Modules\Ws\Tasks\MainTask;
use PhalconKit\Cli\Module as CliModule;
use PhalconKit\Locale;
use PhalconKit\Mvc\Module as MvcModule;
use PhalconKit\Support\Env;

class Config extends \PhalconKit\Bootstrap\Config
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        $this->defineConst();

        $data = $this->internalMergeAppend([
            'app' => [
                'version' => '1.0.0',
            ],

            'modules' => [
                MvcModule::NAME_API => [
                    'className' => \App\Modules\Api\Module::class,
                    'path' => APP_PATH . '/Modules/Api/Module.php',
                ],
                MvcModule::NAME_FRONTEND => [
                    'className' => \App\Modules\Frontend\Module::class,
                    'path' => APP_PATH . '/Modules/Frontend/Module.php',
                ],
                CliModule::NAME_CLI => [
                    'className' => \App\Modules\Cli\Module::class,
                    'path' => APP_PATH . '/Modules/Cli/Module.php',
                ],
                WebSocketModule::NAME_WS => [
                    'className' => \App\Modules\Ws\Module::class,
                    'path' => APP_PATH . '/Modules/Ws/Module.php',
                ],
            ],

            'router' => [
                'defaults' => [
                    'namespace' => Env::get(
                        'ROUTER_DEFAULT_NAMESPACE',
                        'App\\Modules\\Frontend\\Controllers'
                    ),
                    'module' => Env::get('ROUTER_DEFAULT_MODULE', MvcModule::NAME_FRONTEND),
                ],
                'ws' => [
                    'namespace' => Env::get(
                        'ROUTER_WS_DEFAULT_NAMESPACE',
                        'App\\Modules\\Ws\\Tasks'
                    ),
                ],
            ],

            'locale' => [
                'default' => Env::get('LOCALE_DEFAULT', 'en'),
                'mode' => Env::get('LOCALE_MODE', Locale::MODE_DEFAULT),
                'allowed' => explode(',', Env::get('LOCALE_ALLOWED', 'en')),
            ],

            'providers' => [
                \PhalconKit\Provider\Identity\ServiceProvider::class =>
                    \App\Provider\Identity\ServiceProvider::class,
                \App\Provider\Firebase\ServiceProvider::class =>
                    \App\Provider\Firebase\ServiceProvider::class,
            ],

            'models' => [
                \PhalconKit\Models\Audit::class => \App\Models\Audit::class,
                \PhalconKit\Models\AuditDetail::class => \App\Models\AuditDetail::class,
                \PhalconKit\Models\Email::class => \App\Models\Email::class,
                \PhalconKit\Models\File::class => \App\Models\File::class,
                \PhalconKit\Models\User::class => \App\Models\User::class,
                \PhalconKit\Models\UserType::class => \App\Models\UserType::class,
                \PhalconKit\Models\UserGroup::class => \App\Models\UserGroup::class,
                \PhalconKit\Models\UserRole::class => \App\Models\UserRole::class,
                \PhalconKit\Models\Role::class => \App\Models\Role::class,
                \PhalconKit\Models\Group::class => \App\Models\Group::class,
                \PhalconKit\Models\GroupRole::class => \App\Models\GroupRole::class,
                \PhalconKit\Models\Type::class => \App\Models\Type::class,
            ],

            'firebase' => [
                'jsonFile' => Env::get('FIREBASE_JSON_FILE', ''),
                'databaseUri' => Env::get('FIREBASE_DATABASE_URI', ''),
            ],

            'permissions' => [
                'roles' => [
                    'cli' => [
                        'components' => [
                            CronTask::class => ['*'],
                            DatabaseTask::class => ['*'],
                        ],
                    ],
                    'ws' => [
                        'components' => [
                            MainTask::class => ['*'],
                        ],
                    ],
                    'everyone' => [
                        'components' => [
                            IndexController::class => ['*'],
                            AdminController::class => ['*'],
                            ErrorController::class => ['*'],
                        ],
                    ],
                    'visitor' => [
                        'inherit' => [
                            'participant',
                        ],
                    ],
                    'employee' => [
                        'inherit' => [
                            'participant',
                        ],
                    ],
                    'admin' => [
                        'inherit' => [
                            'user',
                        ],
                    ],
                    'dev' => [
                        'inherit' => [
                            'user',
                            'admin',
                        ],
                    ],
                ],
            ],
        ], $data);

        $data = $this->internalMergeAppend($data, new EventConfig()->toArray());
        // Merge the rest of app/Config/Permissions/*Config.php here.

        parent::__construct($data, $insensitive);

        $this->modules->remove(MvcModule::NAME_ADMIN);
    }
}
```

Root config conventions:

- Call `defineConst()` before using constants such as `APP_PATH` if this config
  may be constructed before the parent constructor runs.
- Register app modules with the correct module name constants:
  `MvcModule::NAME_API`, `MvcModule::NAME_FRONTEND`, `CliModule::NAME_CLI`,
  and the app WebSocket module's `NAME_WS`.
- For task permissions, CLI output behavior, WebSocket router defaults, Swoole
  settings, and live channel broadcasts, see `cli-and-websocket.md`.
- Override router namespaces when modules live under `App\Modules`.
- Put provider overrides under `providers`. For a core provider override, keep
  the core provider as the key and the app provider as the value. For a new app
  provider, use the app provider as both key and value.
- For detailed provider examples, see `providers.md`: it includes an
  `identity` override that persists session identity through an app model and a
  new `firebase` provider that reads its own app config section.
- For auth controllers, JWT/session identity, impersonation, ACL role
  inheritance, and security behavior policy, see `identity-and-security.md`.
- Map core model classes to app model classes under `models` so identity,
  permissions, scaffolded controllers, and core services resolve the app
  models.
- For concrete model contracts, generated abstracts, scaffold commands, and
  model alias expectations, see `models-and-scaffolding.md`.
- Add custom integration config under a first-level key such as `firebase`.
  Providers should read that section from DI config.
- For Docker Compose service names, `.env` values, Apache/Nginx PHP-FPM
  proxying, and Swoole runtime settings, see `environment.md`.
- Keep base `cli`, `ws`, and `everyone` roles in root config because they apply
  across the application.
- Merge per-resource permission config files after the base config so their
  feature/role entries are available to ACL and behavior attachment.
- Remove unused core modules after `parent::__construct()` with
  `$this->modules->remove(...)` when the app intentionally disables them.

## Permission Config Composition

Large apps often have many resource configs:

```php
$data = $this->internalMergeAppend($data, new ActivityConfig()->toArray());
$data = $this->internalMergeAppend($data, new AddressConfig()->toArray());
$data = $this->internalMergeAppend($data, new EventConfig()->toArray());
$data = $this->internalMergeAppend($data, new FundraisingConfig()->toArray());
$data = $this->internalMergeAppend($data, new VoteConfig()->toArray());
```

Keep each resource file focused on one bounded area. For example,
`FundraisingConfig` should grant fundraising controllers/models and only attach
behaviors needed for fundraising visibility. Shared role inheritance stays in
the root config unless it is truly resource-specific.

When adding a new API resource, update these config surfaces together:

1. Add app model aliases under `models` if the resource overrides a core model.
2. Add `Config/Permissions/<Resource>Config.php` with `manage<Resource>` and
   `view<Resource>` features where appropriate.
3. Merge the new permission config in `Config/Config.php`.
4. Add controller exposers in `Config/Exposers.php`.
5. Add module/controller code under `Modules/Api/Controllers`.
6. Run the app's ACL/controller tests or at least smoke-test allowed and denied
   identities.
