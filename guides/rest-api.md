# REST Controllers

A REST controller connects an application model to query, write, and export
actions. Start with the complete [Project tutorial](first-rest-resource.md);
use this page to choose the actions and policies your own resource exposes.

## Routes And Actions

The App skeleton uses `/api/{controller}/{action}`. A `ProjectController` exposes
`/api/project/find`, for example. The table shows the intended method for each
operation; explicit action routes need your own method policy.

| Action | Intended method | Success result in `view` |
| --- | --- | --- |
| `find` | GET | `data`: list of matching records, possibly empty |
| `find-with` | GET | `data`: list with an allowed relationship graph |
| `find-first` | GET | `data`: one record; 404 when no record matches |
| `find-first-with` | GET | One record with an allowed relationship graph; 404 when absent |
| `new` | GET | `data`: an unsaved model with defaults and allowed assigned input |
| `create` | POST | `saved`, `mode: "create"`, `data`, `messages`; 201 for a single record |
| `update` | PATCH or PUT | `saved`, `mode: "update"`, `data`, `messages`; requires identity |
| `save` | POST, PUT, PATCH | Create or update according to identity lookup; 200 for a single success |
| `delete` | DELETE | `deleted`, exposed `data`, `messages` |
| `restore` | POST | `restored`, exposed `data`, `messages`; requires soft-delete support |
| `reorder` | POST | `reordered`, exposed `data`, `messages`; requires position support |
| `count` | GET | Scalar or grouped `count`; optional extra totals |
| `distinct` | GET | `field`, scalar `data` list, page `count`; field must be allowed |
| `average`, `sum`, `minimum`, `maximum` | GET | Corresponding calculation; column is controller-configured |
| `export` | GET | An attachment instead of the JSON envelope |

`min` and `max` call `minimum` and `maximum`. Compatibility read aliases `get`,
`get-with`, `get-all`, and `get-all-with` map to first/first-with/find/find-with;
use the explicit `find` names in new clients and permission rules.

The optional `index` action (`/api/project`) forwards by method:

| Request | Forwarded action |
| --- | --- |
| GET without `id` | `find` |
| GET with `id` | `find-first` |
| POST, PUT, PATCH | `save` |
| DELETE | `delete` |

Grant both the entry action and the destination actions when using forwarding.
Use explicit `/create` and `/update` URLs when the client must distinguish intent.
`/api/project/42` is **not** an ID route in the default router; use
`/api/project/find-first?id=42` or define an application route.

## Define Resource Policies

Override the focused initializer for each policy. Query policies initialize
before the query is assembled; setting them after `parent::initialize()` may
leave the prepared query with the previous values.

| Hook | Purpose | Example |
| --- | --- | --- |
| `initializeExposeFields()` | JSON and export visibility | `setExposeFields([false, 'id', 'label'])` |
| `initializeSaveFields()` | Writable model attributes and nested paths | `setSaveFields(['label', 'status'])` |
| `initializeFilterFields()` | Fields clients may filter | `setFilterFields(['id', 'status'])` |
| `initializeSearchFields()` | Fields searched by `search` | `setSearchFields(['label'])` |
| `initializeOrderFields()` | Allowed sort names and trusted mappings | `setOrderFields(['id', 'label'])` |
| `initializeWith()` | Default/allowed eager-loaded graph | `setWith(['TaskList'])` |
| `initializeDistinctActionFields()` | Allowed facet fields | `setDistinctActionFields(['status'])` |
| `initializeFindActionCountFields()` | Allowed list totals | `setFindActionCountFields(['count', 'totalCount'])` |
| `initializeCountActionResponseFields()` | Extra metadata on `/count` | `setCountActionResponseFields(['totalCount'])` |

Policies have different empty/default meanings:

- Exposure defaults to visible fields. Use `[false, ...allowedFields]` for a
  closed policy; `[]` alone is not a deny rule for the exposer.
- `saveFields: null` delegates to model assignment. Use an explicit writable
  list; an empty list permits no input fields.
- `filterFields` and `orderFields` default to unrestricted valid identifiers.
  Explicit lists constrain what clients can query.
- Search needs configured searchable fields.
- `with` and distinct fields are closed unless configured.
- List counts are opt-in per request; their default policy permits the finite
  supported count names. An empty list blocks them.

Never let request input set SQL expressions or these policy arrays directly.
Grant controller actions and model operations in
[permissions](identity-and-permissions.md). Field policies do not grant actions
or restrict which rows a user owns.

## Enforce HTTP Methods

Explicit action names are callable without built-in per-action verb guards.
For a resource offering only reads, create, update, and delete, add this to its
controller:

```php
/** Enforce the HTTP contract before query initialization or writes. */
public function initialize()
{
    $methods = [
        'find' => ['GET'],
        'find-first' => ['GET'],
        'count' => ['GET'],
        'distinct' => ['GET'],
        'create' => ['POST'],
        'update' => ['PATCH', 'PUT'],
        'delete' => ['DELETE'],
    ];
    $action = $this->dispatcher->getActionName();
    // Dispatcher action names may already be camelized.
    $action = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $action));
    $allowed = $methods[$action] ?? [];
    if (!in_array($this->request->getMethod(), $allowed, true)) {
        $this->response->setHeader('Allow', implode(', ', $allowed));
        throw new \PhalconKit\Exception\HttpException('Method not allowed.', 405);
    }
    parent::initialize();
}
```

Add every action you actually grant to that map. Account for CORS preflight in
the dispatcher and test the result with OPTIONS. Cookie-authenticated write
endpoints also need an application CSRF strategy; method checks alone are not
CSRF protection.

## Customize Business Operations

Put persistence validation in the model or an application service so the same
rule applies from HTTP, CLI, and background work. Put request-specific policy
in the controller. For a custom action, return the normal envelope explicitly:

```php
public function capabilitiesAction(): \Phalcon\Http\ResponseInterface
{
    $this->setRestViewVar('capabilities', ['canExport' => false]);
    return $this->setRestResponse(true);
}
```

Grant `capabilities` separately. Extend the action's method policy too.

The save lifecycle supports `rest:beforeAssign`, `rest:beforeSave`, and
`rest:afterSave` hooks. Review the event arguments in the class reference before
attaching a listener; cancel a write with a model message that explains the
rejection. External effects such as email should occur after committed writes,
with retry/idempotency handled by your application.

## Continue The Handbook

[Requests and responses](rest-requests-and-responses.md) ·
[Filters and pagination](rest-filtering.md) · [Writes](rest-writes.md) ·
[Relationships](rest-relationships.md) · [Counts and exports](rest-aggregates.md) ·
[Scenario checklist](rest-scenarios.md)
