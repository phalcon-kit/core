# Migrate RESTful 0.x Resources To 1.x

Use this guide when upgrading an older Zemit/PhalconKit 0.x application to the
1.x REST controller and scaffolding conventions.

This is separate from the package rename. The package rename is covered in
[Migration From zemit-cms/core](migration-from-zemit.md). This guide focuses on
resource code: generated models, REST controller policy methods, eager loading,
permissions, and custom actions.

## What Changed

| 0.x pattern | 1.x pattern |
| --- | --- |
| Phalcon DevTools model generator | PhalconKit database-first scaffolder |
| Generated concrete models carry most schema code | Generated abstracts/interfaces carry schema code; concrete models carry business logic |
| hand-written base relationship and validation setup | generated `addDefaultRelationships()` and `addDefaultValidations()` plus app overrides |
| manual relationship definitions copied into every concrete model | scaffolded relationship definitions plus app-owned relationship additions |
| `Zemit\...` namespaces | `PhalconKit\...` namespaces |
| path constants without trailing slash | normalized `ROOT_PATH`, `VENDOR_PATH`, and `APP_PATH` with trailing slash |
| `class Bootstrap extends \Zemit\Bootstrap` | `class Bootstrap extends \PhalconKit\Bootstrap` |
| `Zemit\Bootstrap\Devtools` devtools bootstrap | `PhalconKit\Bootstrap\Devtools` devtools bootstrap |
| `Zemit\Bootstrap\Config` app config | `PhalconKit\Bootstrap\Config` app config |
| `Zemit\Modules\Api\Module` module base class | `PhalconKit\Modules\Api\Module` module base class |
| `Zemit\Mvc\Module` / `Zemit\Cli\Module` constants | `PhalconKit\Mvc\Module` / `PhalconKit\Cli\Module` constants |
| `Zemit\Models\...` model mapping | `PhalconKit\Models\...` model mapping |
| `getWhiteList()` | `initializeSaveFields()` plus `setSaveFields(array|Collection|null)` |
| `getSearchWhiteList()` | `initializeSearchFields()` plus `setSearchFields(array|Collection|null)` |
| `getFilterWhiteList()` | `initializeFilterFields()` plus `setFilterFields(array|Collection|null)` |
| `getWith()` and `getListWith()` | `initializeWith()`; use `isListRequest()` for list/detail graph differences |
| indexed `getJoins()` arrays | keyed `initializeJoins()` policy arrays or collections |
| `getDynamicJoins()` | `initializeDynamicJoins()` plus `setDynamicJoins(array|Collection|null)` |
| `getExpose()` | `initializeExposeFields()` or transformers; simple resources can keep default expose behavior |
| `getFind()` custom mutation | `initializeConditions()` with named condition blocks or a dedicated trait |
| `getListAction()` runtime/limit overrides | `initializeLimit()` |
| direct `Zemit\Fractal\Manager` setup in `listExpose()` | app transformer helpers or `PhalconKit\Fractal` classes |
| old response view keys such as `single` and `list` | new `data` response shape, or a temporary response adapter |
| shared helpers in old `AbstractController` | app API base controller that extends `PhalconKit\Modules\Api\Controller` |
| `getRoleList()` on controllers | permission config features and roles |
| `getPermissionCondition()` SQL string assembly | `initializePermissionConditions()` with named condition blocks |
| permission `controllers` / `models` split | unified `components` map |
| unreliable 0.x behavior workarounds | event-driven permission behaviors with priorities and explicit event hooks |
| `SkipIdentityCondition` / `SkipSoftDeleteCondition` | `RemoveDefaultPermissionCondition` / `RemoveDefaultSoftDeleteCondition` |
| large controllers with many custom actions | thin resource controller plus resource-specific traits/services |

## Actual 0.4.46 To dev-master Gap

The gap from the latest 0.4.x tag (`0.4.46`) to current `master` /
`dev-master` is larger than a namespace rename. The REST layer, generated model
surface, query behaviors, and scaffolding workflow were all reorganized.

The most important practical changes are:

- Composer package and namespace changed from `zemit-cms/core` / `Zemit\...` to
  `phalcon-kit/core` / `PhalconKit\...`.
- The Phalcon baseline moved from the older 5.9.x package surface toward the
  current 5.17.x surface.
- `Restful::initialize()` now calls `initializeQuery()` as the normal query
  bootstrap path.
- The old monolithic `Mvc\Controller\Traits\Query` getter API was split into
  focused query traits for fields, conditions, joins, dynamic joins, order,
  group, having, limit, offset, cache, bind, save, and compilation.
- Query state is now stored in `Phalcon\Support\Collection` objects and merged
  with policy-aware helpers instead of raw arrays and controller-level `_bind`
  / `_bindTypes` mutation.
- Query initialization now fires granular REST events such as
  `rest:afterInitializeFields`, `rest:afterInitializeConditions`,
  `rest:afterInitializeWith`, and `rest:afterInitializeFind`.
- The behavior system is now part of the normal controller lifecycle. Behaviors
  are attached from permission features, are event driven, can declare event
  type and priority, and can customize individual REST/query phases without
  overriding the whole controller method.
- Old `get*()` policy overrides are compatibility code, not the native 1.x
  style.
- REST read actions moved from the old `GetAction` / `GetListAction` split to
  `FindAction` and `FindFirstAction`. Legacy `get` and `get-all` action methods
  still exist as deprecated bridges that delegate to `find*` actions.
- Save behavior moved into a controller-side `Query\Save` trait with explicit
  `save()`, `create()`, and `update()` flows and normalized REST response
  status handling.
- New aggregate REST actions exist for calculation-style endpoints, including
  count, sum, average, minimum, maximum, and distinct.
- Default row-level permissions are now condition collections. Controller code
  should add or remove named condition entries, not concatenate one SQL string.
- Old skip behaviors are superseded by query-removal behaviors that target
  specific query pieces: conditions, fields, joins, limits, bind values,
  soft-delete conditions, permission conditions, search conditions, and filter
  conditions.
- Filter/search handling is significantly more capable and stricter: dynamic
  joins, alias-aware filtering, bind type inference, filter semantics, and
  existential-style conditions are now first-class query compiler concerns.
- Joined count behavior must be reviewed because current joined filters can
  require distinct primary-key counts.
- Eager-loading definitions still use the same relationship-path idea, and
  Phalcon relationships still work the same way. The main change is that
  scaffolding now generates far more of the relationship and validation surface
  for you.
- Fractal/expose behavior moved toward reusable base-controller helpers,
  explicit expose fields, and transformers.
- Generated model abstracts and interfaces remain central, but the current
  scaffolder generates a richer typed surface, enum classes, stricter method
  signatures, and default relationship/validation helpers.
- Concrete generated models should stay thin: `parent::initialize()`,
  `addDefaultRelationships()`, `genericValidation()`, and
  `addDefaultValidations()` are the generated baseline; application rules stay
  in the app concrete model.
- Core model/resource names changed in places. For example, `Field` became
  `Column`. Treat similar app-level renames as schema/domain migrations, not
  simple class aliases.
- Module-local loader classes were removed from core modules; app bootstrap
  should rely on the root loader and module namespace registration.
- The CLI scaffolding task now supports more generation controls, including
  interfaces, abstracts, concrete models, enums, controllers, typings,
  strict-types, comments, raw value typing, and protected properties.
- Public documentation and bundled AI skill references were added for the new
  REST, scaffolding, model, identity, permission, and transformer patterns.

For migrated applications, this means the safest path is not to patch old
controllers until they run. Instead:

1. Update bootstrap/package/module namespaces first.
2. Regenerate models from the current scaffolder.
3. Port app-owned concrete model logic onto the generated model surface.
4. Replace old behavior workarounds with permission-driven behaviors where the
   1.x lifecycle has a specific event hook.
5. Port controller policy getters to initialization methods.
6. Port custom `getFind()` SQL assembly into named condition collections.
7. Keep a temporary legacy bridge only for frontend compatibility.
8. Remove the bridge once clients use `find`, `find-with`, `find-first`,
   `find-first-with`, `data`, and the new response semantics.

## Recommended Migration Order

Migrate one resource at a time.

1. Upgrade Composer/package references first.
2. Run database migrations against a disposable database.
3. Run the PhalconKit scaffolder.
4. Compare generated model aliases with old controller relation names.
5. Migrate the app API `AbstractController`.
6. Migrate the app root config and permission fragments when package-level
   config changed.
7. Convert REST policy methods.
8. Convert role/action access to permission config.
9. Convert row-level SQL strings to permission condition collections.
10. Split large custom actions into traits or services.
11. Run the old API smoke tests against the new controller.

Avoid mixing a resource migration with broad behavior changes. Preserve the old
request and response contract first, then improve the API after tests are
green.

## Application Config Migration

If the upgrade includes the package rename or 1.x bootstrap changes, update the
root config before migrating individual resources. Controllers, model aliases,
permissions, modules, and providers all depend on it.

Old 0.x config usually extended Zemit classes:

```php
use Zemit\Bootstrap\Config as ZemitConfig;
use Zemit\Mvc\Module as MvcModule;
use Zemit\Cli\Module as CliModule;
use Zemit\Support\Env;
use Zemit\Locale;

class Config extends ZemitConfig
{
}
```

In 1.x, switch the root imports to PhalconKit:

```php
use PhalconKit\Bootstrap\Config as PhalconKitConfig;
use PhalconKit\Mvc\Module as MvcModule;
use PhalconKit\Cli\Module as CliModule;
use PhalconKit\Support\Env;
use PhalconKit\Locale;

class Config extends PhalconKitConfig
{
}
```

Update core model mappings from `Zemit\Models\...` to `PhalconKit\Models\...`:

```php
'models' => [
    \PhalconKit\Models\Audit::class => \App\Models\Audit::class,
    \PhalconKit\Models\AuditDetail::class => \App\Models\AuditDetail::class,
    \PhalconKit\Models\User::class => \App\Models\User::class,
    \PhalconKit\Models\Role::class => \App\Models\Role::class,
],
```

Review provider overrides instead of carrying them forward blindly. Keep
app-owned providers only when the app still needs a replacement or integration
service:

```php
'providers' => [
    \App\Provider\Search\ServiceProvider::class =>
        \App\Provider\Search\ServiceProvider::class,
],
```

Keep permission fragments modular:

```php
$data = $this->internalMergeAppend($data, new AuthConfig()->toArray());
$data = $this->internalMergeAppend($data, new WorkspaceConfig()->toArray());
$data = $this->internalMergeAppend($data, new FooBarConfig()->toArray());
$data = $this->internalMergeAppend($data, new UserConfig()->toArray());
```

When domain names changed, rename the permission fragment and every related
controller/model action reference together. For example, an old
`FooBarReasonConfig` may become `FooBarDecisionConfig` if the model and
API language changed.

Use environment values for credentials and external-service configuration:

```php
'openai' => [
    'apiKey' => Env::get('OPENAI_SECRET_KEY'),
    'organization' => Env::get('OPENAI_ORGANIZATION_ID'),
    'project' => Env::get('OPENAI_PROJECT_ID'),
    'baseUri' => Env::get('OPENAI_BASE_URI'),
],
```

Do not migrate hard-coded secrets from old config files. Move them to `.env`,
deployment secrets, or the host secret store. If real credentials were ever
committed to a repository, rotate them before publishing or tagging the
migration.

Config migration checklist:

- loader path constants use the app's chosen trailing-slash convention
  consistently;
- app bootstrap extends `PhalconKit\Bootstrap` and imports
  `PhalconKit\Support\Env`;
- `devtools.php` imports `PhalconKit\Bootstrap\Devtools`;
- root config extends `PhalconKit\Bootstrap\Config`;
- module classes extend the matching `PhalconKit\Modules\...\Module` base;
- module constants are imported from `PhalconKit\Mvc\Module` and
  `PhalconKit\Cli\Module`;
- `Env` and `Locale` imports use `PhalconKit\...`;
- core model mappings use `PhalconKit\Models\...`;
- app provider overrides are reviewed and kept only when needed;
- permission fragments are renamed with domain/model changes;
- role inheritance is preserved intentionally;
- CLI/WebSocket task permissions include new task classes;
- external-service secrets are read from environment values, not literals.

## Loader Path Migration

Before migrating config and modules, normalize the app loader path constants.
Older apps often used constants without trailing slashes:

```php
const ROOT_PATH = __DIR__;
const VENDOR_PATH = ROOT_PATH . '/vendor';
const APP_PATH = ROOT_PATH . '/app';

$loader = new Loader();
$loader->setFiles([VENDOR_PATH . '/autoload.php']);
$loader->setNamespaces([APP_NAMESPACE => APP_PATH]);
```

The 1.x app convention can keep trailing slashes in the constants and simplify
path concatenation:

```php
const ROOT_PATH = __DIR__ . '/';
const VENDOR_PATH = ROOT_PATH . 'vendor/';
const APP_PATH = ROOT_PATH . 'app/';

$loader = new Loader();
$loader->setFiles([VENDOR_PATH . 'autoload.php']);
$loader->setNamespaces([APP_NAMESPACE => APP_PATH]);
```

Pick one convention and apply it consistently. Mixing trailing-slash constants
with old `'/file.php'` concatenation is a common source of doubled separators
and broken module paths in migration diffs.

## Devtools Bootstrap Migration

If the app keeps a root `devtools.php` entrypoint for scaffolding, update the
bootstrap import from Zemit to PhalconKit:

```php
use Zemit\Bootstrap\Devtools;
use App\Config\Config;

$loader = require 'loader.php';

$config = new Config();
return new Devtools($config->toArray());
```

The 1.x version keeps the same app config handoff:

```php
use PhalconKit\Bootstrap\Devtools;
use App\Config\Config;

$loader = require 'loader.php';

$config = new Config();
return new Devtools($config->toArray());
```

Verify this file before running scaffolding. A stale devtools bootstrap can
make the generator load old namespaces even after the application runtime has
already been migrated.

## Application Bootstrap Migration

The application bootstrap usually keeps the same responsibility: pick any
environment-file override, instantiate app config, and hand that config to the
framework bootstrap.

Old 0.x bootstrap classes extended Zemit and imported Zemit helpers:

```php
namespace App;

use App\Config\Config;
use Zemit\Support\Env;

class Bootstrap extends \Zemit\Bootstrap
{
    public function initialize(): void
    {
        if ($_COOKIE['X-E2E-Test'] ?? false) {
            Env::setNames(['.env.e2e']);
        }

        $this->setConfig(new Config());
    }
}
```

The 1.x version keeps the same app logic and swaps the framework namespace:

```php
namespace App;

use App\Config\Config;
use PhalconKit\Support\Env;

class Bootstrap extends \PhalconKit\Bootstrap
{
    public function initialize(): void
    {
        if ($_COOKIE['X-E2E-Test'] ?? false) {
            Env::setNames(['.env.e2e']);
        }

        $this->setConfig(new Config());
    }
}
```

Keep app-specific environment names private to the application. Public examples
should use generic names such as `.env.e2e`, `.env.testing`, or
`.env.local`.

## Module Class Migration

Application module classes usually need only a base-class rename. Keep
application namespace registrations intact:

```php
namespace App\Modules\Api;

class Module extends \Zemit\Modules\Api\Module
{
    final public function getNamespaces(): array
    {
        return array_merge([
            'App\\Models' => APP_PATH . '/Models/',
        ], parent::getNamespaces());
    }
}
```

The 1.x equivalent is:

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

Do this for each app module that extends a Zemit module base class. Then verify
the root config still points to the same app module class and file path.

## Temporary Legacy Bridge

If the frontend still depends on old 0.x action names, response keys, and
controller override methods, use a short-lived compatibility trait instead of
copying legacy behavior into every migrated controller.

This bridge is not part of the native 1.x controller API. Treat it as
application-owned migration code unless the project explicitly opts into a
separate compatibility layer.

A bridge trait can attach to REST lifecycle events and translate old controller
methods into 1.x collections:

```php
trait LegacyTrait
{
    public function initializeLegacy(): void
    {
        $this->eventsManager->attach(
            'rest:afterInitializeFields',
            fn () => $this->mergeSaveFields($this->getWhiteList())
        );

        $this->eventsManager->attach(
            'rest:afterInitializeFields',
            fn () => $this->mergeFilterFields($this->getFilterWhiteList())
        );

        $this->eventsManager->attach(
            'rest:afterInitializeWith',
            fn () => $this->mergeWith($this->getLegacyWith())
        );

        $this->eventsManager->attach(
            'rest:afterInitializeFind',
            fn () => $this->setFind($this->getLegacyFind())
        );

        $this->initializeBeforeAssign();

        $this->setMaxLimit(-1);
        $this->setLimit(1000);
    }
}
```

This lets an old controller migrate in stages:

1. Add the legacy trait to keep the old frontend working.
2. Convert one old method at a time to the new `initialize*()` method.
3. Remove the old deprecated method when no controller uses it.
4. Remove response-shape adapters after the frontend reads the 1.x response
   contract.

Keep the bridge in app code, not in new resource examples. Mark old methods
with `#[\Deprecated]` so the migration path is visible during review:

```php
#[\Deprecated(message: 'use initializeSaveFields() instead', since: '1.0')]
public function getWhiteList(): array
{
    return [];
}
```

The bridge is useful for real upgrades, but it should not become the permanent
controller style. PhalconKit core already keeps some narrow deprecated action
aliases, such as old read actions delegating to `find*` actions. A full 0.x
policy bridge is broader: it maps old getter methods, response keys, list
semantics, old counts, and assignment hooks.

Do not copy an app-specific bridge directly into core. If several applications
need the same migration path, add a small opt-in compatibility trait under a
clearly named compatibility namespace, for example:

```php
use PhalconKit\Mvc\Controller\Traits\Compatibility\LegacyRest0x;
```

Such a trait should:

- be disabled by default;
- be explicitly used by an app base controller;
- avoid app-specific defaults such as custom limits, custom roles, or custom
  response variables unless they are configurable;
- mark old methods with `#[\Deprecated]`;
- document which 0.x methods it maps;
- include tests for each mapped lifecycle event;
- have a clear removal or long-term-support policy.

For most application migrations, keeping the bridge in app code is safer. Move
only generic, tested, framework-level compatibility into PhalconKit core.

Useful bridge targets:

- `rest:afterInitializeFields` for old whitelist/search/filter/expose methods;
- `rest:afterInitializeJoins` and `rest:afterInitializeDynamicJoins`;
- `rest:afterInitializeGroup` for old grouped count/list behavior;
- `rest:afterInitializeWith` for old list/detail relation graphs;
- `rest:afterInitializeFind` for old `getFind()` overrides;
- `rest:beforeAssign` for old assignment hooks.

Keep old helper methods such as `getSingle()` and `saveEntity()` in the bridge
only while old custom actions still call them. New code should call
`findFirst()`, `findFirstWith()`, model `save()`, and the normal REST response
helpers directly.

## App API Base Controller

Migrate the application API base controller before individual resources. It is
the best place for shared compatibility and application-specific helpers.

Old 0.x base controllers often extended the Zemit API controller and returned
raw SQL permission strings:

```php
abstract class AbstractController extends \Zemit\Modules\Api\Controller
{
    public function getWorkspaceIdPermissionCondition(
        string $workspaceIdField = '[App\Models\Workspace].[id]',
        bool $orNull = true,
        array $escapeRoleList = ['dev', 'admin']
    ): ?string {
        if ($this->identity->hasRole($escapeRoleList)) {
            return '1';
        }

        $workspaceIdList = $this->getWorkspaceIdList();
        if (empty($workspaceIdList)) {
            return '(0)';
        }

        $this->setBind(['workspaceIdList' => $workspaceIdList]);
        $this->setBindTypes(['workspaceIdList' => Column::BIND_PARAM_INT]);

        return $workspaceIdField . ' in ({workspaceIdList:array})';
    }
}
```

In 1.x, extend the PhalconKit API controller, initialize the temporary bridge
if needed, and return condition blocks instead of mutating controller-level
bind state:

```php
abstract class AbstractController extends \PhalconKit\Modules\Api\Controller
{
    use LegacyTrait;

    public function initialize()
    {
        $this->initializeLegacy();

        parent::initialize();
    }

    public function getWorkspaceIdPermissionCondition(
        string $field,
        bool $orNull = true,
        bool $orOwner = true
    ): ?array {
        if ($this->identity->hasRole($this->getSuperRoles())) {
            return null;
        }

        $workspaceIdList = $this->getWorkspaceIdList($orOwner);
        if (empty($workspaceIdList)) {
            return [0];
        }

        $field = $this->appendModelName($field);
        $orEmpty = $orNull ? "or {$field} is null or {$field} = ''" : '';
        $bindKey = '_' . uniqid('workspaceIdList') . '_';

        return [
            trim("$field in ({{$bindKey}:array}) $orEmpty"),
            [$bindKey => $workspaceIdList],
            [$bindKey => Column::BIND_PARAM_INT],
        ];
    }
}
```

This lets resource controllers use:

```php
$this->getPermissionConditions()->set(
    'workspaceId',
    $this->getWorkspaceIdPermissionCondition('id')
);
```

Common helpers to move into the app API base controller:

- `getWorkspaceIdList()` and similar identity-scoped ID lists;
- `getWorkspaceIdPermissionCondition()` and `getUserIdPermissionCondition()`;
- `queryBuilderOrderBy()` for relation-level eager-loading ordering;
- shared eager-loading definitions such as form relation graphs;
- `getSavableColumnsFromModel()` for resources that save generated nested
  models;
- `isAdmin()` or app-specific role shortcuts;
- transformer fallback in `expose()` and `listExpose()`;
- temporary legacy bridge initialization.

Do not put resource-specific filters or business workflows in the base
controller. Keep those in the resource controller, a resource trait, or a
domain service.

## Model Generation

Older applications often used Phalcon DevTools to generate models directly.
That made the concrete model both schema output and business-code owner.

In 1.x, the database remains the source of truth, but the scaffolder is much
more capable. It separates generated structure from application behavior and
can produce the default relationship definitions, validation rules, interfaces,
abstracts, enums, and typed method surface that older applications often had to
maintain by hand.

For an existing application, regenerate generated layers without overwriting
concrete models while you are still porting custom logic:

```shell
./vendor/bin/phalcon-kit cli scaffold run \
  --src-dir=app/ \
  --namespace=App \
  --models-extend=\\App\\Models\\AbstractModel \
  --force \
  --no-models
```

For a new application or a missing concrete model shell, omit `--no-models` so
the scaffolder can create the concrete model file too.

Review the generated diff for:

- abstract model column maps, accessors, comments, and built-in validations;
- generated interfaces;
- enum classes;
- relationship aliases such as `UserNode`, `UserEntity`, `GroupList`;
- many-to-many and node-table aliases;
- uniqueness validation from indexes.

Keep custom methods, custom relationships, state transitions, and domain rules
in the concrete model.

## Concrete Model Migration

Old DevTools-era concrete models often contained everything: constants,
defaults, base relationships, validation rules, transform behaviors, lifecycle
hooks, and domain helpers.

```php
class FooBar extends AbstractFooBar
{
    const STATUS_PENDING = 'pending';

    protected $sourceType = self::SOURCE_TYPE_LITERATURE;
    protected $primaryStatus = self::STATUS_PENDING;

    public function initialize(): void
    {
        parent::initialize();

        $this->belongsTo('workspaceId', Workspace::class, 'id', ['alias' => 'WorkspaceEntity']);
        $this->hasMany('id', FooBarReview::class, 'fooBarId', ['alias' => 'FooBarReviewList']);
        $this->hasManyToMany(
            'id',
            FooBarLabel::class,
            'fooBarId',
            'labelId',
            Label::class,
            'id',
            ['alias' => 'LabelList']
        );
    }
}
```

In 1.x, the generated abstract/interface layer owns the schema-derived pieces.
The concrete model should call the generated defaults, then add only the
relationships and behavior the scaffolder cannot infer safely:

```php
use App\Models\Abstracts\FooBarAbstract;
use App\Models\Interfaces\FooBarInterface;
use PhalconKit\Db\Column;
use PhalconKit\Mvc\Model\Behavior\Transformable;

class FooBar extends FooBarAbstract implements FooBarInterface
{
    const string STATUS_PENDING = 'pending';
    const string STATUS_PASS = 'pass';

    public mixed $sourceType = self::SOURCE_TYPE_LITERATURE;
    public mixed $primaryStatus = self::STATUS_PENDING;

    public bool $skipPreviousStatesOnCreate = true;

    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();

        $this->hasMany('id', FooBarUser::class, 'fooBarId', ['alias' => 'UserNode']);
        $this->hasManyToMany(
            'id',
            FooBarUser::class,
            'fooBarId',
            'userId',
            User::class,
            'id',
            ['alias' => 'UserList']
        );

        $this->addBehavior(new Transformable([
            'beforeValidation' => [
                'uid' => $this->forceNullEmptyField(),
                'doi' => $this->forceNullEmptyField(),
                'url' => $this->forceNullEmptyField(),
                'title' => $this->forceNullEmptyField(),
            ],
        ]));
    }
}
```

The porting goal is a lean concrete model. Move over application-owned logic:
constants, default values that are not schema defaults, lifecycle rules, custom
relationships, prompt/build helpers, and domain methods. Do not copy
scaffolded logic from the old concrete model when the new abstract class or
interface already owns it. The new abstract/interface layer is the generated
contract; the concrete model is the application extension point.

Validation follows the same rule. Let generated defaults handle schema-derived
requirements, then add business-specific constraints:

```php
public function validation(): bool
{
    $validator = $this->genericValidation();
    $this->addDefaultValidations($validator);

    $this->addUnsignedIntValidation($validator, 'workspaceId', false);
    $this->addNumberValidation($validator, 'year', 1900, (int)date('Y') + 1, true);
    $this->addStringLengthValidation($validator, 'abstract', 0, Column::TEXT_MAX_LENGTH, true);
    $this->addStringLengthValidation($validator, 'uid', 1, 60, true);
    $this->validateWorkspaceId($validator, 'workspaceId');

    return !count($this->getMessages()) && $this->validate($validator);
}
```

Model migration checklist:

- rename old `AbstractFooBar` imports to generated `FooBarAbstract`;
- implement the generated app-facing interface when available;
- keep the concrete model lean by relying on generated abstract/interface
  logic for scaffolded columns, relationships, accessors, and validation;
- replace `Zemit\Db\Column` and behaviors with `PhalconKit\...` imports;
- use typed constants and typed/public defaults where that matches the
  generated model style;
- call `addDefaultRelationships()` before app-only relationship overrides;
- call `addDefaultValidations()` before app-only validation rules;
- keep lifecycle hooks such as `beforeValidationOnCreate()` in the concrete
  model when they express business rules;
- keep domain helpers such as prompt builders in the concrete model or a domain
  service;
- move relationship aliases into controllers only as policy references, not as
  relationship definitions.

Review generated aliases after every scaffold run. If the scaffolder creates
duplicate or unwanted aliases, keep the generated defaults stable and add the
app-specific alias in the concrete model until the scaffold configuration or
generator can be improved safely.

Status, node, and join-table models usually get even smaller after migration.
Old concrete models often repeated relationship definitions and low-level
validators:

```php
class FooBarReview extends AbstractFooBarReview
{
    const STATUS_PENDING = 'pending';
    const TYPE_PRIMARY = 'primary';

    protected $deleted = self::NO;

    public function initialize(): void
    {
        parent::initialize();

        $this->hasOne('fooBarId', FooBar::class, 'id', ['alias' => 'FooBarEntity']);
        $this->hasOne('userId', User::class, 'id', ['alias' => 'UserEntity']);
        $this->hasMany('id', FooBarReviewReason::class, 'fooBarReviewId', [
            'alias' => 'FooBarReviewReasonNode',
        ]);
    }
}
```

In 1.x, let the generated model own default relationships and validations, then
keep only the application invariant:

```php
class FooBarReview extends FooBarReviewAbstract implements FooBarReviewInterface
{
    const string STATUS_PENDING = 'pending';
    const string TYPE_PRIMARY = 'primary';

    public mixed $deleted = self::NO;

    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();
    }

    public function validation(): bool
    {
        $validator = $this->genericValidation();
        $this->addDefaultValidations($validator);

        $this->addUniquenessValidation($validator, [
            'fooBarId',
            'userId',
            'type',
            'userType',
        ]);
        $this->validateWorkspaceId($validator, 'workspaceId');

        return !count($this->getMessages()) && $this->validate($validator);
    }

    public function beforeValidation(): void
    {
        if ($this->hasChanged(['workspaceId', 'fooBarId'])) {
            $this->enforceWorkspaceIdFromFooBarId($this->getFooBarId());
        }
    }
}
```

For node/status models, check whether old nullable cases still exist. For
example, if old review rows allowed an AI `userType`, but 1.x stores AI
reviews in `AutomatedReview`, remove that special case and enforce normal user
status uniqueness.

## Controller Policy Methods

Old 0.x controllers returned arrays from getter methods:

```php
public function getWhiteList()
{
    return [
        'label',
        'description',
        'usernode' => [
            'userId',
            'type',
            'deleted',
        ],
    ];
}

public function getSearchWhiteList()
{
    return [
        'id',
        'label',
        'description',
    ];
}

public function getFilterWhiteList()
{
    return [
        'id',
        'label',
        'UserNode.userId',
    ];
}
```

In 1.x, initialize the policy arrays explicitly:

```php
public function initializeSaveFields(): void
{
    $this->setSaveFields([
        'label',
        'description',
        'usernode' => [
            'userId',
            'type',
            'deleted',
        ],
    ]);
}

public function initializeSearchFields(): void
{
    $this->setSearchFields([
        'id',
        'label',
        'description',
    ]);
}

public function initializeFilterFields(): void
{
    $this->setFilterFields([
        'id',
        'label',
        'UserNode.userId',
    ]);
}
```

Use the migration as a chance to keep the lists separate. Do not move every old
field into every new policy list unless the API really allows that operation.

From 3.1, REST/query policy setters and merge helpers accept either arrays or
`Phalcon\Support\Collection` instances. If an application controller overrides
one of these framework methods, update the override signature to match the
widened parent contract. For example, `setSaveFields(?Collection $fields)` must
become `setSaveFields(array|Collection|null $fields)`, and
`mergeSaveFields(Collection $fields)` must become
`mergeSaveFields(array|Collection $fields)`.

For large nested save graphs, do not retype every scaffolded column by hand.
Use model metadata for schema-owned fields, then attach only the relation graph
that the old frontend still posts:

```php
public function initializeSaveFields(): void
{
    $formQuestionList = $this->getSavableColumnsFromModel(new FormQuestion());
    $formQuestionList['formpromptlist'] =
        $this->getSavableColumnsFromModel(new FormPrompt());
    $formQuestionList['formchoicelist'] =
        $this->getSavableColumnsFromModel(new FormChoice());

    $formGroupList = $this->getSavableColumnsFromModel(new FormGroup());
    $formGroupList['formquestionlist'] = $formQuestionList;

    $formSaveFields = $this->getSavableColumnsFromModel(new Form());
    $formSaveFields['formgrouplist'] = $formGroupList;
    $formSaveFields['formquestionlist'] = $formQuestionList;

    $this->setSaveFields($formSaveFields);
}
```

This keeps save policies aligned with generated model columns while preserving
legacy lowercase relation keys such as `formquestionlist` and
`formchoicelist`. Keep hand-written allow-lists when the controller must
intentionally expose only a small subset of model fields.

Simple resources should migrate mechanically. If a controller only had
whitelist/search/filter arrays, move them directly to `initialize*Fields()` and
avoid adding a legacy bridge:

```php
class AnnotationController extends AbstractController
{
    public function initializeSaveFields(): void
    {
        $this->setSaveFields([
            'documentId',
            'hash',
            'color',
            'note',
            'content',
            'deleted',
        ]);
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields([
            'hash',
            'color',
            'note',
            'content',
        ]);
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields([
            'workspaceId',
            'fooBarId',
            'documentId',
            'hash',
            'color',
            'note',
            'content',
            'deleted',
            'createdBy',
            'createdAt',
        ]);
    }
}
```

## Eager Loading

Eager loading is conceptually the same as before: it still follows Phalcon
relationship aliases and nested relationship paths. The migration is mostly
about moving from legacy getter methods to initializer methods, and verifying
that the aliases generated by the new scaffolder match the paths used by the
controller.

Old controllers commonly had separate `getWith()` and `getListWith()` methods:

```php
public function getWith()
{
    return [
        'UserNode.UserEntity',
        'FormList',
        'FormList.FormGroupList',
        'FormList.FormGroupList.FormQuestionList',
        'GroupList',
    ];
}

public function getListWith()
{
    return [
        'UserNode.UserEntity',
        'GroupList',
    ];
}
```

In 1.x, use `initializeWith()` and branch on the request shape:

```php
public function initializeWith(): void
{
    if ($this->isListRequest()) {
        $this->setWith([
            'UserNode.UserEntity',
            'GroupList',
        ]);
        return;
    }

    $this->setWith([
        'UserNode.UserEntity',
        'GroupList',
        'FooBarDecisionList',
        'FormList',
        ...$this->getFormEagerLoadingDefinition('FormList.'),
    ]);
}
```

Keep list graphs small. Use richer graphs for detail/edit screens or custom
actions that actually need the nested data.

If the old detail graph was empty but the list graph loaded a small user
relation, either keep the old split or deliberately use one graph for both
request shapes:

```php
public function initializeWith(): void
{
    $this->setWith([
        'CreatedByEntity',
        'UserEntity',
    ]);
}
```

When doing this, update expose fields at the same time so the newly loaded
relations are intentionally returned.

## Joins

Old joins were often plain indexed arrays:

```php
public function getJoins()
{
    return [
        [
            WorkspaceUser::class,
            '[' . $this->getModelName() . '].[id] = [UserNode].[workspaceId]',
            'UserNode',
            'left',
        ],
    ];
}
```

In 1.x, key joins by alias:

```php
public function initializeJoins(): void
{
    $this->setJoins([
        'UserNode' => [
            WorkspaceUser::class,
            '[' . $this->getModelName() . '].[id] = [UserNode].[workspaceId]',
            'UserNode',
            'left',
        ],
    ]);
}
```

Use the same alias consistently in filter fields, search fields, dynamic joins,
permission conditions, and eager-loading graphs.

## Dynamic Joins

High-filter resources often used `getDynamicJoins()` so joins were only added
when filters/search/order paths referenced a related alias:

```php
public function getDynamicJoins(): array
{
    return [
        'Note' => [
            Note::class,
            '[' . $this->getModelName() . '].[id] = [Note].[fooBarId]'
            . ' and [Note].[deleted] <> 1',
        ],
        'FooBarReview.FooBarReviewReason' => [
            FooBarReviewReason::class,
            '[FooBarReview].[id] = '
            . '[FooBarReview.FooBarReviewReason].[fooBarReviewId]',
        ],
    ];
}
```

In 1.x, initialize a policy array:

```php
public function initializeDynamicJoins(): void
{
    $this->setDynamicJoins([
        'Note' => [
            Note::class,
            '[' . $this->getModelName() . '].[id] = [Note].[fooBarId]'
            . ' and [Note].[deleted] <> 1',
        ],
        'FooBarReview.FooBarDecisionReason' => [
            FooBarDecisionReason::class,
            '[FooBarReview].[id] = '
            . '[FooBarReview.FooBarDecisionReason].[fooBarReviewId]'
            . ' and [FooBarReview.FooBarDecisionReason].[deleted] <> 1',
        ],
    ]);
}
```

This is also the place to update renamed domain aliases. For example, if an old
resource used `FooBarReason`, the 1.x model may expose
`FooBarDecision`; update filter fields, dynamic joins, eager-loaded relations,
and transformers together.

## Permissions

In 0.x, a controller could expose broad role lists and concatenate SQL
conditions:

```php
public function getRoleList()
{
    return ['user', 'dev', 'admin', 'manager', 'reviewer', 'editor'];
}

public function getPermissionCondition($type = null, $identity = null)
{
    $permissionCondition[] = parent::getPermissionCondition();
    $permissionCondition[] = $this->getWorkspaceIdPermissionCondition(
        '[' . $this->getModelName() . '].[id]'
    );

    return '(' . implode(') AND (', array_filter($permissionCondition)) . ')';
}
```

In 1.x, put action/model access in permission config and row-level access in the
controller:

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    $this->getPermissionConditions()->set(
        'workspaceId',
        $this->getWorkspaceIdPermissionCondition('id')
    );
}
```

The permission config decides which role can call `find`, `find-with`,
`create`, `update`, custom actions, model `find`, model `save`, CLI tasks, and
WebSocket tasks. The controller condition decides which rows the current
identity may see.

Some simple resources need one extra permission pass. If the default identity
condition would restrict list results to rows created by the current user, but
the old endpoint allowed users to see all rows for their assigned workspaces,
remove the default condition only for list requests and keep the workspace
condition:

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    if ($this->isListRequest()) {
        $this->getPermissionConditions()->remove('default');
    }

    if (!$this->identity->hasRole($this->getSuperRoles())) {
        $this->getPermissionConditions()->set(
            'workspaceId',
            $this->getWorkspaceIdPermissionCondition('workspaceId')
        );
    }
}
```

This preserves broad read access within assigned workspaces while keeping
save/update/delete/restore operations constrained by the remaining default
conditions or model/controller checks.

## Behavior System Migration

In many 0.x applications, controller behaviors were not reliable enough to be
the primary customization mechanism. Applications often worked around that by
overriding `getFind()`, `getPermissionCondition()`, `getListAction()`, or
controller-specific save methods.

In 1.x, behaviors are expected to be used. They are attached during
`beforeExecuteRoute()` from the resolved permission roles and features, then
run through the controller event manager. The query lifecycle exposes granular
events, so a behavior can customize one phase without taking ownership of the
whole controller action.

Important REST lifecycle events include:

- `rest:beforeInitializeQuery`;
- `rest:afterInitializeFields`;
- `rest:afterInitializeJoins`;
- `rest:afterInitializeDynamicJoins`;
- `rest:afterInitializeConditions`;
- `rest:afterInitializeGroup`;
- `rest:afterInitializeOrder`;
- `rest:afterInitializeLimit`;
- `rest:afterInitializeWith`;
- `rest:afterInitializeBind`;
- `rest:afterInitializeFind`;
- `rest:afterInitializeQuery`;
- `rest:beforeSave`;
- `rest:afterSave`.

Attach behaviors from permission features:

```php
'features' => [
    'exportFooBarList' => [
        'components' => [
            FooBarController::class => ['export'],
            FooBar::class => ['find'],
        ],
        'behaviors' => [
            FooBarController::class => [
                RemoveDefaultPermissionCondition::class,
                RemoveDefaultSoftDeleteCondition::class,
                AddExportWorkspaceCondition::class,
            ],
        ],
    ],
],
```

When a behavior is keyed by the current controller class, it is attached to the
`rest` event type. When it is keyed by the controller's model class, it is
attached to the `model` event type. A behavior can also declare its own event
type or priority:

```php
use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

final class AddExportWorkspaceCondition
{
    public string $eventType = 'rest';
    public int $priority = 200;

    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getPermissionConditions()->set(
            'exportWorkspace',
            $controller->getWorkspaceIdPermissionCondition('workspaceId', false)
        );
    }
}
```

Prefer this pattern for cross-cutting query changes:

- remove or replace default permission conditions for one feature;
- remove default soft-delete constraints for administrative restore views;
- disable or change default limits for exports;
- add feature-specific joins, conditions, groups, or expose fields;
- keep compatibility behavior in one bridge instead of in every resource
  controller.

Treat old `Skip\...` behavior classes as migration compatibility names. For
new 1.x code, prefer the explicit `Query\Remove...` behavior classes because
they describe the query piece being changed and hook into the improved
event-driven lifecycle.

## Permission Config Migration

Old permission fragments could use a mix of `components`, `controllers`, and
`models`, plus skip behaviors:

```php
use Zemit\Config\Config as ZemitConfig;
use Zemit\Mvc\Controller\Behavior\Skip\SkipIdentityCondition;
use Zemit\Mvc\Controller\Behavior\Skip\SkipSoftDeleteCondition;

class FooBarConfig extends ZemitConfig
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'viewFooBarList' => [
                        'components' => [
                            FooBarController::class => ['get', 'get-all'],
                            FooBar::class => ['find'],
                        ],
                        'behaviors' => [
                            FooBarController::class => [
                                SkipIdentityCondition::class,
                            ],
                        ],
                    ],
                    'assignExistingFooBarReview' => [
                        'controllers' => [
                            FooBarController::class => ['save-review-status'],
                        ],
                        'models' => [
                            FooBarReview::class => ['find'],
                            FooBarReviewReason::class => ['find', 'create', 'update'],
                        ],
                    ],
                ],
            ],
        ], $data);
    }
}
```

In 1.x, extend `PhalconKit\Config\Config`, use one `components` map, and attach
query-condition removers explicitly:

```php
use PhalconKit\Config\Config as PhalconKitConfig;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultPermissionCondition;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultSoftDeleteCondition;

class FooBarConfig extends PhalconKitConfig
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        $data = $this->internalMergeAppend([
            'permissions' => [
                'features' => [
                    'manageFooBarList' => [
                        'components' => [
                            FooBarController::class => ['get', 'get-all', 'save', 'delete', 'restore', 'count'],
                            FooBar::class => ['find', 'findFirst', 'create', 'update', 'delete', 'restore', 'count'],
                            FooBarReview::class => ['find', 'findFirst', 'create', 'update', 'delete', 'restore', 'count'],
                        ],
                        'behaviors' => [
                            FooBarController::class => [
                                RemoveDefaultPermissionCondition::class,
                                RemoveDefaultSoftDeleteCondition::class,
                            ],
                        ],
                    ],
                ],
            ],
        ], $data);
    }
}
```

Use migration as a chance to split broad old features into workflow-specific
features:

- `manageFooBarList` for normal resource CRUD and list/count access;
- `importFooBarList` for import endpoints and create/update model methods;
- `exportFooBarList` for export endpoints and read-only related models;
- `viewFooBarMetrics` for metrics endpoints and read-only status models;
- `saveFooBarReview` for review-status save flows;
- `assignFooBarUserList` for assignment workflows.

Action names must match the controller action key that the permission system
checks. If a legacy route still calls `save-review-status`, keep that action in
the feature until the route is removed. If the migrated route is
`assign-user`, grant `assign-user`, not `assignUser`.

Behavior migration rules:

- `SkipIdentityCondition` usually maps to
  `RemoveDefaultPermissionCondition`.
- `SkipSoftDeleteCondition` usually maps to
  `RemoveDefaultSoftDeleteCondition`.
- If a feature removes default row conditions, document where contextual
  permissions are enforced instead, usually in the controller or model.
- Keep destructive model methods such as `delete`, `restore`, and `update`
  scoped to the smallest feature that needs them.
- Include `findFirst` and `count` when the migrated controller or helper calls
  those methods directly.

Permission config checklist:

- permission fragments extend `PhalconKit\Config\Config`;
- `controllers` and `models` maps are folded into `components`;
- old skip behaviors are replaced with query condition removers;
- action names match actual route/action names;
- workflow-specific features replace one broad catch-all when practical;
- role feature assignments preserve the old effective permissions;
- renamed domain models are updated everywhere, for example
  `FooBarReviewReason` to `FooBarDecisionReason`.

## Advanced Query Conditions

Older controllers often overrode `getFind()` and appended joins, binds, and SQL
fragments directly:

```php
public function getFind()
{
    $find = parent::getFind();
    $advanced = $this->getParam('advanced') ?? [];

    if (!empty($advanced['userId']) && !empty($advanced['userStatus'])) {
        $find['joins'][] = [
            FooBarReview::class,
            '[' . FooBar::class . '].[id] = [SpecificFooBarReview].[fooBarId]'
            . ' and [SpecificFooBarReview].[userId] = :specificUserId:',
            'SpecificFooBarReview',
            'inner',
        ];
    }

    return $find;
}
```

In 1.x, keep custom condition compilation separate from the final find array.
Use `initializeConditions()` and delegate complex semantic filters to a trait:

```php
use App\Modules\Api\Controllers\FooBar\FooBarAdvanced;

class FooBarController extends AbstractController
{
    use FooBarAdvanced;

    public function initializeConditions(): void
    {
        parent::initializeConditions();

        $this->prepareAdvancedConditions();
    }
}
```

Inside the trait, add named condition blocks instead of mutating one large
`$find` array. Prefer `EXISTS` / `NOT EXISTS` for conflict and reviewed filters
so joins do not multiply result rows:

```php
$conditions = new Collection([]);
$this->getConditions()->set('advanced', $conditions);

$key = $this->generateBindKey('specific_user_id');

$conditions->set('specific_user_status', [
    'conditions' => '
        EXISTS (
            SELECT 1
            FROM ' . FooBarReview::class . ' rus
            WHERE rus.fooBarId = [' . FooBar::class . '].[id]
              AND rus.userId = :' . $key . ':
        )
    ',
    'bind' => [
        $key => (int)$advanced['userId'],
    ],
    'bindTypes' => [
        $key => Column::BIND_PARAM_INT,
    ],
]);
```

Use unique bind keys for every generated block. This avoids collisions when
multiple filters are combined.

## Runtime Limits

Old controllers sometimes disabled runtime limits in `initialize()` or in a
list action:

```php
public function getListAction(): ResponseInterface
{
    Utils::setUnlimitedRuntime();
    return parent::getListAction();
}
```

In 1.x, keep process runtime overrides explicit and initialize query limits
before the action runs:

```php
use PhalconKit\Support\Utils;

public function initializeLimit(): void
{
    Utils::setUnlimitedRuntime();
    $this->setMaxLimit(-1);
    $this->setLimit(-1);

    parent::initializeLimit();
}
```

Only use unlimited limits for intentional high-volume workflows such as import,
export, assignment, or metrics. Normal API lists should keep a finite default
limit.

If the old frontend expects high default limits, set them in the compatibility
trait while migrating. Do not silently preserve `setMaxLimit(-1)` for public
list endpoints unless the endpoint is explicitly designed for bulk work.

## Exposers And Transformers

Old resources often used:

```php
public function getExpose()
{
    return [
        true,
    ];
}
```

For 1.x resources:

- keep default exposing for internal/simple CRUD;
- use `initializeExposeFields()` when a small whitelist is enough;
- use transformers when public clients need stable response contracts, renamed
  fields, conditional includes, or relation-loaded output.

Transformers are usually the best long-term choice for public APIs because they
make response shape explicit and can avoid accidental lazy-loading.

If an old resource manually created a Zemit Fractal manager:

```php
public function listExpose(iterable $items, ?array $listExpose = null): array
{
    $fractal = new Manager();
    $fractal->setSerializer(new RawArraySerializer());
    $collection = new Collection($items, new FooBarTransformer());

    return $fractal->createData($collection)->toArray();
}
```

Move imports to `PhalconKit\Fractal\Manager` and
`PhalconKit\Fractal\Serializer\RawArraySerializer`, or use the app/core
transform helper if the base controller already exposes one. Keep transformer
includes aligned with `initializeWith()` so includes read loaded relations
instead of triggering lazy loads.

For many apps, the base API controller can resolve the transformer from the
controller name:

```php
public function listExpose(iterable $items, ?array $expose = null): array
{
    if ($this->hasExposeFields()) {
        return parent::listExpose($items, $expose);
    }

    $transformerName = $this->getTransformerNameFromController();
    if ($transformerName) {
        $fractal = new Manager();
        $fractal->setSerializer(new RawArraySerializer());

        return $fractal
            ->createData(new Collection($items, new $transformerName()))
            ->toArray();
    }

    return parent::listExpose($items, $expose);
}
```

This keeps individual resource controllers focused on policies and domain
actions. If a resource needs special output, override its transformer instead of
adding one-off response formatting to the controller.

Simple resources can collapse old `getExpose()` and `getListExpose()` methods
into one `initializeExposeFields()` when list and detail responses should now
share the same shape:

```php
public function initializeExposeFields(): void
{
    $this->setExposeFields([
        false,
        'id',
        'workspaceId',
        'fooBarId',
        'documentId',
        'hash',
        'color',
        'note',
        'content',
        'deleted',
        'createdAt',
        'updatedAt',
        'CreatedByEntity' => [
            false,
            'id',
            'firstName',
            'lastName',
            'email',
        ],
        'UserEntity' => [
            false,
            'id',
            'firstName',
            'lastName',
            'email',
        ],
    ]);
}
```

If list and detail responses must remain different for compatibility, branch in
`initializeExposeFields()` using `isListRequest()` or keep the difference in a
temporary legacy bridge.

## Legacy Response Shape

The 1.x REST response shape normally exposes data through `data`. Older
frontends may expect:

- `single` for one resource;
- `list` for collections;
- `totalCount` on list endpoints;
- save results with `single` instead of `data`.

Keep that compatibility in one response adapter:

```php
public function transformResponseView(ResponseInterface $response): ResponseInterface
{
    $content = json_decode($response->getContent(), true);
    if (!isset($content['view'])) {
        return $response;
    }

    $view = &$content['view'];
    if ((isset($view['saved']) || isset($view['deleted'])) && isset($view['data'])) {
        $viewKey = array_is_list($view['data']) ? 'list' : 'single';
        $view[$viewKey] = $view['data'];
        unset($view['data']);
    }

    return $response->setJsonContent($content);
}
```

Use this only while old clients are still deployed. New clients should read the
normal 1.x response contract.

## Counts With Joins

Old list endpoints often returned `totalCount` even when filters introduced
joins. Joined filters can duplicate rows, so a count needs to count distinct
primary keys.

If a legacy bridge preserves old `get-all` responses, centralize that behavior:

```php
public function count(?array $find = null): ResultsetInterface|int|false
{
    $find ??= $this->prepareFind();
    $find = $this->getCalculationFind($find);

    if (!empty($find['joins']) && empty($find['column'])) {
        $primaryKey = reset($this->getPrimaryKeyAttributes());
        if ($primaryKey) {
            $find['column'] = 'DISTINCT ' . $this->appendModelName($primaryKey);
        }
    }

    return $this->loadModel()::count($find);
}
```

Review this once the frontend moves to 1.x endpoints. Some high-volume
endpoints may intentionally skip counts; normal paginated endpoints should
return accurate counts.

## Custom Actions

Old controllers sometimes accumulated large custom actions such as progress,
reset, duplicate, import, export, compare, assign, status-save, advanced
filter, or metrics logic. During migration, keep the resource controller
readable:

```php
class FooBarController extends AbstractController
{
    use FooBarAssign;
    use FooBarCompare;
    use FooBarExport;
    use FooBarImport;
    use FooBarMetrics;
    use FooBarStatus;
    use FooBarAdvanced;
}
```

Good candidates for traits or services:

- progress calculations;
- custom sorting that cannot be expressed directly in PHQL;
- workspace duplication;
- reset/recompute actions;
- import/export flows;
- resource assignment;
- similarity/compare operations;
- status-save preparation in `beforeAssign()`;
- advanced reviewed/conflict filters;
- long-running maintenance actions.

Keep the controller action responsible for request/response, identity, and
permission-aware lookup. Move the domain work into a trait or service when it
gets large.

For custom workflows, a useful 1.x structure is one trait per workflow:

```php
namespace App\Modules\Api\Controllers\FooBar;

trait FooBarMetrics
{
    public function getAllMetricsAction(): ResponseInterface
    {
        // ...
    }
}
```

This keeps the main resource controller declarative and makes it clear which
permissions need to grant each custom action.

### Metrics And Bulk Calculations

Metrics actions should reuse the normal query preparation so filters,
permission conditions, dynamic joins, and bind values still apply:

```php
public function getAllMetricsAction(): ResponseInterface
{
    $find = $this->prepareFind() ?: [];

    // Metrics must process the whole filtered set, not only the current page.
    unset($find['limit']);

    $fooBarList = FooBar::findWith([
        'FooBarReviewList',
        'FooBarAutomationReviewList',
    ], $find);

    $metrics = $this->buildFooBarMetrics($fooBarList);

    if ($this->getParam('advanced')['details'] ?? false) {
        $this->view->setVar('details', $metrics);
    }

    $this->view->setVar('metrics', $this->collapseMetricSetsToCounts($metrics));

    return $this->setRestResponse(true);
}
```

For staged workflows, isolate previous-stage rules behind helpers. This avoids
duplicating hard-coded `if` trees across full metrics and per-user/per-status
count actions:

```php
public function prepareFindForMetrics(
    string $stage,
    array $defaultFind,
    bool $bindMetricsStage = true
): array {
    $find = $defaultFind;
    $eligibleStatusSql = "'" . implode("', '", $this->getEligiblePreviousStageStatusListForMetrics()) . "'";

    foreach ($this->getPreviousStageListFromStage($stage) as $previousStage) {
        $find['conditions'] .= ' and [' . $previousStage . 'Status] in (' . $eligibleStatusSql . ')';
    }

    $find['joins'] ??= [];

    if ($bindMetricsStage) {
        $find['bind']['metricsStage'] = $stage;
        $find['bindTypes']['metricsStage'] = Column::BIND_PARAM_STR;
    }

    return $find;
}

protected function getEligiblePreviousStageStatusListForMetrics(): array
{
    return [
        FooBar::STATUS_PASS,
        FooBar::STATUS_SKIP,
    ];
}
```

Prefer small count helpers for each metric perspective:

- official/resource status counts;
- user-specific review counts;
- role or user-type counts;
- automated review counts;
- conflict counts between any two perspectives.

When count helpers add joins, use dedicated aliases and bind names such as
`MetricsFooBarReview`, `metricsUserId`, and `metricsStage`. Group by the
resource primary key when the metric should count one conflict per resource.

### Workflow Save Actions

Custom save actions can add action-specific save fields, attach save events,
and then delegate to the normal `saveAction()`:

```php
public function saveReviewStatusAction(): ResponseInterface
{
    $this->mergeSaveFields([
        'foobarreviewlist' => [
            'type',
            'userId',
            'userType',
            'status',
            'FooBarReviewReasonList' => [
                'reasonId',
                'deleted',
            ],
        ],
    ]);

    $wasCompleteBeforeSave = false;

    $this->eventsManager->attach(
        'rest:beforeSave',
        function ($event, $controller, array $params) use (&$wasCompleteBeforeSave) {
            $fooBar = $params[0] ?? null;
            if ($fooBar instanceof FooBar) {
                $wasCompleteBeforeSave = WorkspaceProgress::isCurrentStageComplete(
                    $this->db,
                    $fooBar->getWorkspaceEntity()
                );
            }
        }
    );

    $this->eventsManager->attach(
        'rest:afterSave',
        function ($event, $controller, array $params) use (&$wasCompleteBeforeSave) {
            $fooBar = $params[0] ?? null;
            if (!$fooBar instanceof FooBar || $wasCompleteBeforeSave) {
                return;
            }

            $workspace = $fooBar->getWorkspaceEntity();
            if (WorkspaceProgress::isCurrentStageComplete($this->db, $workspace)) {
                WorkspaceNotification::sendCompleted($workspace);
            }
        }
    );

    return $this->saveAction();
}
```

Use this pattern when the workflow needs the standard save pipeline, nested
relation saving, response handling, and `rest:beforeSave` / `rest:afterSave`
events, but has a different input contract from normal CRUD.

### Import Actions

Import actions are usually better as explicit custom actions than as overloaded
`save` calls:

```php
public function importAction(): array
{
    Utils::setUnlimitedRuntime();

    $resultList = [];
    $payload = $this->getParams();
    unset($payload['_url']);

    foreach ($payload as $row) {
        unset($row['id']);

        $fooBar = FooBar::findFirst([
            'workspaceId = :workspaceId: and externalKey = :externalKey:',
            'bind' => [
                'workspaceId' => $row['workspaceId'],
                'externalKey' => $row['externalKey'],
            ],
            'bindTypes' => [
                'workspaceId' => Column::BIND_PARAM_INT,
                'externalKey' => Column::BIND_PARAM_STR,
            ],
        ]) ?: new FooBar();

        // Imports may need different lifecycle defaults than interactive saves.
        $fooBar->skipPreviousStagesOnCreate = false;
        $fooBar->assign($row, $this->getSaveFields()->toArray());

        $result = [
            'saved' => $fooBar->save(),
            'messages' => $fooBar->getMessages(),
            'data' => $fooBar->toArray(),
        ];

        if (!$result['saved'] || !empty($result['messages'])) {
            $resultList[] = $result;
        }
    }

    return $resultList;
}
```

The important parts are:

- strip client-owned identifiers before assignment;
- normalize or parse import-only fields before `assign()`;
- find existing rows with a stable dedupe key;
- keep import lifecycle flags explicit;
- assign with `getSaveFields()->toArray()`;
- return only failures if the import endpoint is meant to be quiet on success;
- give the action its own permission feature.

## Save Hooks

Old `beforeAssign()` hooks can usually stay, but review them carefully because
field policies and relation aliases may have changed.

Typical migration checks:

- replace old relation aliases with scaffolded 1.x aliases;
- remove response-only relations from the posted payload before assignment;
- keep action-specific save behavior behind the action name or a dedicated
  custom action;
- update nested relation keys such as status reason nodes when models were
  renamed;
- keep identity-derived values server-side instead of accepting them from the
  client.

```php
public function beforeAssign(
    ModelInterface &$entity,
    array &$post,
    ?array &$whiteList,
    ?array &$columnMap
): void {
    array_unset_recursive($post, [
        'workspaceentity',
        'labellist',
        'notelist',
        'documentlist',
    ]);
}
```

## Custom Ordering Example

If an old endpoint sorted by calculated data after loading rows, migrate it
intentionally.

When the value can be expressed by the database, rewrite the virtual order key
to a PHQL expression or subquery in `initializeOrder()`:

```php
public function initializeOrder(): void
{
    parent::initializeOrder();

    $order = $this->getOrder();
    if (!$order?->has('totalFields')) {
        return;
    }

    $direction = str_contains(strtolower((string)$order->get('totalFields')), ' desc')
        ? 'desc'
        : 'asc';

    $order->set('totalFields', $this->getTotalFieldsExpression() . ' ' . $direction);
}

private function getTotalFieldsExpression(): string
{
    return '(SELECT COUNT(*) FROM [' . FormQuestion::class . '] '
        . 'WHERE [' . FormQuestion::class . '].[formId] = [' . $this->getModelName() . '].[id] '
        . 'AND [' . FormQuestion::class . '].[deleted] <> 1)';
}
```

If the value cannot be sorted safely by the database, use an explicit
in-memory sorting path. The 1.x pattern is:

1. Detect the virtual order field in `initializeOrder()`.
2. Remove it from the normal SQL order.
3. Fetch matching IDs.
4. Compute the virtual values.
5. Page IDs in computed order.
6. Reload the current page with `findWith()`.
7. Preserve the requested order before exposing the list.

```php
public function initializeOrder(): void
{
    parent::initializeOrder();

    $order = $this->getOrder();
    if (!$order?->has('completionPercent')) {
        return;
    }

    $this->completionPercentOrderDirection = str_contains(
        strtolower((string)$order->get('completionPercent')),
        ' desc'
    ) ? 'desc' : 'asc';

    $order->remove('completionPercent');
    if ($order->count() === 0) {
        $this->setOrder(null);
    }
}
```

Keep the in-memory pattern for values that depend on application-only
calculation, external services, or post-query aggregation.

## Action Name Mapping

Exact routes depend on the app router, but old resource actions usually map to
the current model-backed actions like this:

| 0.x action/intent | 1.x action |
| --- | --- |
| `get-list` / list resources | `find` |
| `get-all` / list resources with relations | `find-with` |
| `get` / get one resource with relations | `find-first-with` |
| `save` / save create-update payload | `save` |
| create only | `create` |
| update only | `update` |
| soft delete | `delete` |
| restore soft-deleted row | `restore` |
| `save-review-status` or similar alias | custom action that delegates to `saveAction()` or a service |
| custom workflow | keep an app-owned action and add permission config |

If clients depend on old URLs, keep route aliases during the migration and
remove them in a later version.

When keeping old aliases, override `isListRequest()` and similar request-shape
helpers in the bridge so list/detail eager-loading behavior still matches the
old frontend:

```php
public function isListRequest(): bool
{
    return in_array($this->dispatcher->getActionName(), ['get-all', 'get-list'], true);
}
```

## Resource Checklist

For each migrated resource, verify:

- package imports use `PhalconKit\...`, not `Zemit\...`;
- root app config and model mappings use `PhalconKit\...`;
- app module classes extend `PhalconKit\Modules\...\Module`;
- provider overrides were reviewed instead of copied blindly;
- secrets were moved to environment/deployment secret storage;
- the app API base controller extends `PhalconKit\Modules\Api\Controller`;
- shared permission helpers return condition blocks, not raw SQL plus global
  bind mutation;
- models are generated by PhalconKit scaffolding;
- concrete models contain only app-owned behavior;
- concrete models call generated default relationships and validations before
  app-specific overrides;
- status/node models keep business invariants such as uniqueness and workspace
  ownership enforcement in concrete model hooks;
- duplicated or renamed relationship aliases are reviewed after scaffold runs;
- save/search/filter/expose policies are explicit;
- large nested save policies use model metadata helpers when the intended
  policy is "all savable columns plus these nested relation keys";
- list and detail eager-loading graphs are intentionally different;
- joins are keyed by alias;
- filter/search fields that reference related labels have matching joins;
- permission config grants the right controller/model actions;
- permission fragments use unified `components` maps;
- old skip behaviors are replaced with explicit condition-remover behaviors;
- default identity conditions are reviewed by request type before being removed
  for broader list access;
- row-level permission conditions are named collection entries;
- advanced filters are installed through `initializeConditions()`;
- dynamic joins are installed through `initializeDynamicJoins()`;
- long-running resource defaults are installed through `initializeLimit()`;
- old `Zemit\Fractal` imports are replaced with `PhalconKit\Fractal` imports
  or base-controller transformer helpers;
- virtual order keys are either rewritten to SQL expressions or handled by an
  explicit computed-sort path;
- custom actions return `ResponseInterface` when they build REST responses;
- custom action permissions are listed in config;
- legacy route aliases are isolated in one bridge or router layer;
- legacy response keys are isolated in one response adapter;
- joined counts use distinct primary keys or are intentionally disabled;
- old request payloads still bind nested relations correctly;
- old response shapes are preserved or intentionally versioned;
- tests cover list, detail, save, permission denial, and one custom action.

## Related Guides

- [Migration From zemit-cms/core](migration-from-zemit.md)
- [Database And Scaffolding](database-scaffolding.md)
- [REST APIs](rest-api.md)
- [Models And Eager Loading](models-and-eager-loading.md)
- [Identity And Permissions](identity-and-permissions.md)
