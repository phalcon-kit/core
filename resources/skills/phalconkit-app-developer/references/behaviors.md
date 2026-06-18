# PhalconKit Controller Behaviors

Use this reference when adding, reviewing, or debugging controller behaviors in
a PhalconKit application. Behaviors are permission-driven hooks that alter the
REST controller lifecycle for specific roles, features, controllers, or models.

For the broader identity, ACL, and permission-policy flow that attaches these
behaviors, read `references/identity-and-security.md`.

## Phalcon Baseline

Native Phalcon references:

- Controllers: https://docs.phalcon.io/5.15/controllers/
- Dispatcher API: https://docs.phalcon.io/5.15/api/phalcon_mvc/#mvcdispatcher
- Events manager: https://docs.phalcon.io/5.15/events/
- ACL: https://docs.phalcon.io/5.15/acl/

PhalconKit controller behaviors are permission-attached hooks around native
controller, dispatcher, event, and ACL flow. Use native docs for dispatch/event
semantics and this file for PhalconKit behavior attachment and query-condition
mutation rules.

## How Behaviors Attach

Controller behaviors are usually declared in permission config:

```php
'permissions' => [
    'features' => [
        'manageEvent' => [
            'components' => [
                EventController::class => ['*'],
            ],
            'behaviors' => [
                EventController::class => [
                    RemoveDefaultPermissionCondition::class,
                    RemoveDefaultSoftDeleteConditionWhileFiltering::class,
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
    ],
],
```

At `beforeExecuteRoute()`, PhalconKit reads the current role permissions,
expands feature entries, and attaches matching behavior classes to the
controller events manager.

Attachment rules:

- A behavior keyed by the current controller class is attached to `rest` events.
- A behavior keyed by the current model class is attached to `model` events when
  the controller exposes `getModelName()`.
- A behavior may define `$eventType` or `$priority`; otherwise `rest` and the
  default event priority are used.
- The `everyone` role is always considered; other roles attach only when the
  current identity has that role.

## REST Query Lifecycle

`Restful::initializeQuery()` fires hooks in this order:

```text
rest:beforeInitializeQuery
rest:afterInitializeCacheConfig
rest:afterInitializeFields
rest:afterInitializeJoins
rest:afterInitializeDynamicJoins
rest:afterInitializeConditions
rest:afterInitializeDistinct
rest:afterInitializeGroup
rest:afterInitializeHaving
rest:afterInitializeOrder
rest:afterInitializeLimit
rest:afterInitializeOffset
rest:afterInitializeWith
rest:afterInitializeBind
rest:afterInitializeBindTypes
rest:afterInitializeFind
rest:afterInitializeQuery
```

Name behavior methods after the event suffix. For example, a behavior that
changes conditions should usually implement `afterInitializeConditions()`.

## Custom Condition Behavior

Use a custom behavior when the same condition should be injected by config
instead of hard-coded into every controller action. This example enforces a
UUID identity condition from the request parameter.

```php
namespace App\Modules\Api\Behaviors;

use Phalcon\Db\Column;
use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

class EnforceUuidIdentityConditionFromParam
{
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $uuid = $controller->getParam('uuid');

        if ($uuid === null || $uuid === '') {
            return;
        }

        $controller->getIdentityConditions()->set('uuid', [
            'uuid = :uuid:',
            'bind' => ['uuid' => $uuid],
            'bindTypes' => ['uuid' => Column::BIND_PARAM_STR],
        ]);
    }
}
```

Attach it from a feature that grants the matching route:

```php
'behaviors' => [
    EventController::class => [
        \App\Modules\Api\Behaviors\EnforceUuidIdentityConditionFromParam::class,
    ],
],
```

Guidelines:

- Validate missing parameters explicitly when absence should be an error. The
  example above skips missing UUIDs so normal identity conditions still decide
  the query.
- Use bind values and bind types for every dynamic value.
- Use a named key such as `uuid` so later behaviors can remove or replace the
  condition intentionally.
- Prefer `generateBindKey()` when a behavior may add multiple conditions with
  common parameter names.

## Condition Collections

REST conditions are initialized as separate collections, then grouped into the
final `conditions` collection:

- `permission`: row-level access based on identity and ownership columns.
- `softDelete`: default active-row filtering for soft-delete models.
- `identity`: model identity filters from request params, usually primary keys.
- `filter`: client filters from allowed filter fields.
- `search`: broad search from allowed search fields.

Use the narrow collection when changing one concern:

```php
$controller->getPermissionConditions()?->remove('default');
$controller->getSoftDeleteConditions()?->clear();
$controller->getIdentityConditions()?->set('uuid', $condition);
```

Only use `getConditions()` when the behavior intentionally changes the whole
condition group after it has been assembled.

## Remove Behaviors

`PhalconKit\Mvc\Controller\Behavior\Query\Conditions` contains focused removers
for condition collections:

| Behavior | Effect |
| --- | --- |
| `RemoveDefaultPermissionCondition` | Removes only the `default` permission condition. |
| `RemovePermissionConditions` | Clears all permission conditions. |
| `RemoveDefaultSoftDeleteCondition` | Removes only the default soft-delete condition. |
| `RemoveDefaultSoftDeleteConditionWhileFiltering` | Removes the default soft-delete condition only when the request filters by `deleted`. |
| `RemoveSoftDeleteConditionsWhileFiltering` | Clears all soft-delete conditions only when the request filters by `deleted`. |
| `RemoveDefaultIdentityCondition` | Removes only the `default` identity condition. |
| `RemoveIdentityConditions` | Clears all identity conditions. |
| `RemoveDefaultFilterCondition` | Removes only the default filter condition. |
| `RemoveFilterConditions` | Clears all filter conditions. |
| `RemoveDefaultSearchCondition` | Removes only the default search condition. |
| `RemoveSearchConditions` | Clears all search conditions. |

Use the `RemoveDefault*` version when a controller adds replacement custom
conditions and should keep any other named conditions. Use the plural
`Remove*Conditions` version only when that entire condition family should not
apply for that role/action.

Other query-shape removers live under
`PhalconKit\Mvc\Controller\Behavior\Query`:

```text
RemoveBind
RemoveCacheConfig
RemoveColumn
RemoveConditions
RemoveDefaultLimit
RemoveDistinct
RemoveGroup
RemoveHaving
RemoveJoins
RemoveLimit
RemoveMaxLimit
RemoveOffset
RemoveWith
```

Field removers live under
`PhalconKit\Mvc\Controller\Behavior\Query\Fields`:

```text
RemoveExposeFields
RemoveFilterFields
RemoveMapFields
RemoveSaveFields
RemoveSearchFields
```

## Skip Behaviors

`PhalconKit\Mvc\Controller\Behavior\Skip` contains classes such as
`SkipLimit`, `SkipOrder`, `SkipPermissionCondition`, and
`SkipSoftDeleteCondition`.

For new app work, prefer the `Query\Remove*` behaviors because they line up with
the current `initializeQuery()` lifecycle hooks. Use `Skip*` behaviors when the
local app or core config already uses them for the same controller family, and
verify the exact event or method hook is active in the installed PhalconKit
version.

## Custom Behavior Checklist

When adding a behavior:

1. Pick the latest lifecycle hook that has the data you need, but runs before
   query compilation.
2. Keep the behavior narrow: one behavior should change one query concern.
3. Use the controller's existing helpers, such as `getParam()`,
   `appendModelName()`, `generateBindKey()`, and condition collections.
4. Register it under the permission feature that needs it.
5. Test at least one role with the behavior and one role without it.
6. For condition removals, test that hidden rows stay hidden for roles that
   should not receive the behavior.

## Security Notes

- Removing permission or soft-delete conditions changes row visibility.
- Never attach broad removal behaviors to `everyone` unless the endpoint is
  intentionally public.
- Keep action-specific exceptions in the controller when only one action needs
  different access.
- Keep behavior classes free of secrets, network calls, and business workflows;
  they should shape framework behavior, not perform the domain operation.
