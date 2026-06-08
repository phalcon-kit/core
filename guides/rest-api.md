# REST APIs

PhalconKit helps you build model-backed REST APIs without writing the same
controller plumbing for every table.

You declare what the resource allows. The framework handles request parameters,
query compilation, save payloads, relation loading, response formatting,
permission conditions, and common REST actions.

Official Phalcon references:

- Controllers: https://docs.phalcon.io/5.13/controllers/
- Request: https://docs.phalcon.io/5.13/request/
- Response: https://docs.phalcon.io/5.13/response/
- PHQL: https://docs.phalcon.io/5.13/db-phql/

## What You Get

A model-backed resource can expose standard actions such as:

```text
/api/project/find
/api/project/find-with
/api/project/find-first
/api/project/find-first-with
/api/project/save
/api/project/create
/api/project/update
/api/project/delete
/api/project/restore
```

Exact URLs depend on your route configuration, but the pattern is the same:
controller actions map to resource operations.

## Build A Resource Controller

Start with the app API base controller and declare the resource policy:

```php
<?php

namespace App\Modules\Api\Controllers;

use Phalcon\Support\Collection;

final class ProjectController extends AbstractController
{
    public function initializeSaveFields(): void
    {
        $this->setSaveFields(new Collection([
            'label',
            'status',
            'usernode' => [
                'userId',
                'type',
                'deleted',
            ],
        ]));
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields(new Collection([
            'id',
            'label',
            'status',
            'UserNode.userId',
            'UserNode.type',
            'deleted',
        ]));
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(new Collection([
            'id',
            'label',
            'status',
        ]));
    }

    public function initializeWith(): void
    {
        $this->setWith(new Collection([
            'UserNode.UserEntity',
        ]));
    }
}
```

This small controller tells PhalconKit:

- which fields can be written;
- which fields can be filtered;
- which fields participate in text search;
- which relations should load with `find-with` and `find-first-with`;
- which nested relation payloads can be saved.

## Keep Policies Separate

Do not use one field list for everything. A real API usually needs different
rules:

- `save fields`: client may write these.
- `filter fields`: client may query these.
- `search fields`: broad text search uses these.
- `expose fields`: response may include these.
- `with`: relations that should be eager loaded.
- `joins`: relations needed for filtering, ordering, or permission checks.
- `permission conditions`: rows the current identity may access.

This keeps public API behavior explicit and reviewable.

For legacy compatibility, a null filter-field policy keeps request filters
unrestricted. Passing an empty filter-field collection is different: it keeps
the policy explicit but rejects every client filter. New resources should define
filter fields instead of relying on unrestricted filtering.

Filter and search field policies accept value-list entries and enabled-map
entries. Enabled maps use boolean-like normalization consistently across REST
policies: `true`, `1`, `'1'`, `'yes'`, and `'on'` enable a key, while `false`,
`0`, `'0'`, `'false'`, `'no'`, and `'off'` disable it.

## Response Relationships On Demand

`findAction()` never eager-loads relationships. Use it for plain lists where
the response should contain only the exposed root records.

`findWithAction()` and `findFirstWithAction()` use the controller's configured
`with` collection when the frontend does not send a `with` parameter. When the
frontend does send `with`, PhalconKit treats the configured collection as an
allow-list and loads only the requested subset.

Configured `with` collections accept normal relation lists, callable relation
constraints, and enabled-map entries. Enabled-map values use the same
boolean-like normalization as field policies, so a merged config can disable a
relation with values such as `false`, `0`, `'0'`, or `'off'`.

```text
GET /api/projects/find-with
GET /api/projects/find-with?with=OwnerEntity,StatusEntity
GET /api/projects/find-with?with=OwnerEntity.ProfileEntity.AvatarFile
GET /api/projects/find-with?with[]=OwnerEntity&with[]=StatusEntity
GET /api/projects/find-with?with[OwnerEntity.ProfileEntity]=1
```

Nested paths can be requested directly. A request for
`OwnerEntity.ProfileEntity.AvatarFile` is passed to the eager loader as one
path, and the loader resolves the required parent relationships internally. If
the configured graph contains a deeper path such as
`OwnerEntity.ProfileEntity.AvatarFile`, clients may request a parent subset
such as `OwnerEntity.ProfileEntity` without also loading the avatar relation.

The inverse is not allowed: configuring `OwnerEntity` does not let a client
request `OwnerEntity.ProfileEntity`. Leave `with` null or set an empty
collection to deny all request-time relationship selection.

## Save Nested Payloads

If the generated model has a relation alias such as `UserNode`, you can allow a
nested save payload:

```json
{
  "label": "Systematic Review 2026",
  "status": "active",
  "usernode": [
    {
      "userId": 10,
      "type": "leader"
    },
    {
      "userId": 11,
      "type": "member"
    }
  ]
}
```

The controller decides which nested fields are accepted. The model layer handles
relationship assignment, validation messages, and save behavior.

## Common Request Patterns

Filter active projects assigned to a user, search text fields, sort newest
first, and limit the page:

```http
GET /api/project/find-with?filter[status]=active&filter[UserNode.userId]=10&search=review&order[id]=desc&limit=20&offset=0
```

Create a resource with a nested relation:

```http
POST /api/project/create
Content-Type: application/json

{
  "label": "Systematic Review 2026",
  "status": "active",
  "usernode": [
    {
      "userId": 10,
      "type": "leader"
    }
  ]
}
```

Patch an existing resource with the same save policy:

```http
POST /api/project/update
Content-Type: application/json

{
  "id": 42,
  "status": "archived"
}
```

Those requests all flow through the controller policy: unknown save fields,
unknown filters, disallowed joins, and unauthorized rows are rejected or ignored
according to the app's REST configuration.

## Filter On Related Data

Use joins when filters or ordering depend on related tables:

```php
public function initializeJoins(): void
{
    $this->setJoins(new Collection([
        'UserNode' => [
            \App\Models\ProjectUser::class,
            '[' . $this->getModelName() . '].[id] = [UserNode].[projectId]',
            'UserNode',
            'left',
        ],
    ]));
}
```

Use dynamic joins when only some filter/search paths need the join. That keeps
normal list requests lighter.

## Restrict Rows By User Or Role

Feature permissions decide whether a role can use a controller/action. Row-level
conditions decide which records that role can access.

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    if (!$this->identity->hasRole($this->getSuperRoles())) {
        $this->getPermissionConditions()->set(
            'projectId',
            $this->getProjectIdPermissionCondition('id')
        );
    }
}
```

This is where you enforce project, workspace, tenant, ownership, or assignment
scoping.

## Use Transformers For Stable Responses

Exposers are fast to configure for simple CRUD. Transformers are better for
external APIs or complex nested data.

```php
use App\Modules\Api\Transformers\ProjectTransformer;
use Phalcon\Http\ResponseInterface;

public function dashboardAction(): ResponseInterface
{
    $projects = $this->findWith([
        'UserNode.UserEntity',
    ], [
        'limit' => 20,
        'order' => '[' . $this->getModelName() . '].[id] DESC',
    ]);

    $this->setRestViewVar(
        self::REST_VIEW_DATA,
        $this->transformCollection($projects, new ProjectTransformer())
    );

    return $this->setRestResponse(true);
}
```

Use transformers when response shape, field names, includes, or performance
need tighter control than a simple expose list.

## REST Response Contract

REST responses keep the existing envelope:

```json
{
  "timestamp": "2026-05-25T18:30:00-04:00",
  "status": "OK",
  "code": 200,
  "response": true,
  "view": {
    "data": []
  }
}
```

Use the response constants and helpers when adding custom actions:

```php
public function dashboardAction(): ResponseInterface
{
    $this->setRestViewVars([
        self::REST_VIEW_DATA => $this->listExpose($this->find()),
        self::REST_VIEW_COUNT => $this->count(),
    ]);

    return $this->setRestResponse(true);
}
```

The standard envelope keys are `REST_PAYLOAD_TIMESTAMP`,
`REST_PAYLOAD_STATUS`, `REST_PAYLOAD_CODE`, `REST_PAYLOAD_RESPONSE`,
`REST_PAYLOAD_VIEW`, and `REST_PAYLOAD_DEBUG`.

The standard view keys include `REST_VIEW_DATA`, `REST_VIEW_MESSAGES`,
`REST_VIEW_COUNT`, `REST_VIEW_FIELD`, `REST_VIEW_SUM`, `REST_VIEW_AVERAGE`,
`REST_VIEW_MINIMUM`, `REST_VIEW_MAXIMUM`, `REST_VIEW_SAVED`,
`REST_VIEW_RESULTS`, `REST_VIEW_STATS`, `REST_VIEW_DELETED`,
`REST_VIEW_RESTORED`, and `REST_VIEW_REORDERED`.

The helpers do not change the payload shape. They make response contracts
discoverable and keep string literals out of framework and app controllers.

## REST Failure Status Codes

Standard REST actions use error responses for failed saves, deletes, restores,
and reorders. A failed operation with validation or domain messages normally
returns `422 Unprocessable Entity`. A failed operation with no messages returns
`400 Bad Request`, because the framework has no domain-specific reason to
expose.

PhalconKit also preserves explicit HTTP client-error codes attached to Phalcon
messages when the code is in the `400-499` range. Framework paths use this for
request-shape failures such as invalid create/update intent, missing update
targets, forbidden operations, or conflicts. Ordinary validation messages with
code `0`, non-HTTP codes, server-error codes, strings, or arrays do not
override the action's default status.

Use this sparingly in application code. Message codes should represent real
client-error HTTP semantics, not arbitrary business error numbers. Server-error
responses should come from exceptions or explicit controller error handling.

## Advanced Conditions

For set logic, `EXISTS` often avoids duplicate rows from joins:

```php
$key = $this->generateBindKey('assigned_user_id');

$this->getConditions()->set('assigned_user', [
    'conditions' => '
        EXISTS (
            SELECT 1
            FROM ' . \App\Models\ProjectUser::class . ' pu
            WHERE pu.projectId = [' . $this->getModelName() . '].[id]
              AND pu.userId = :' . $key . ':
              AND pu.deleted <> 1
        )
    ',
    'bind' => [
        $key => (int)$this->identity->getUserId(),
    ],
    'bindTypes' => [
        $key => \Phalcon\Db\Column::BIND_PARAM_INT,
    ],
]);
```

Keep advanced filter logic in private methods or traits so the controller stays
readable.

## Grouped Count Responses

`countAction()` keeps the native Phalcon result in the `count` response field.
When a controller has a `group` clause, that value can be a grouped count
result instead of a scalar total.

By default, `countAction()` still returns only `count`. Clients can request
optional metadata with the same `count` parameter syntax used by list endpoints:

```http
GET /resources/count?count=totalCount
GET /resources/count?count=groupedCount,bucketTotal,totalCount
GET /resources/count?count[totalCount]=1
```

`?count=1` and `?count=true` are valid but only request the native `count`
field, which the count action already returns.

Controllers that need to always emit dashboard or facet metadata, or restrict
which optional fields clients may request, can configure explicit extra fields:

```php
use Phalcon\Support\Collection;

public function initializeCountActionResponseFields(): void
{
    $this->setCountActionResponseFields(new Collection([
        self::COUNT_RESPONSE_GROUPED_COUNT,
        self::COUNT_RESPONSE_BUCKET_TOTAL,
        self::COUNT_RESPONSE_TOTAL_COUNT,
    ], false));
}
```

Collection policies accept either value-list entries or enabled-map entries.
For count field policies, enabled-map values use boolean-like normalization:
`true`, `1`, `'1'`, and `'yes'` enable the key, while `false`, `0`, `'0'`,
`'false'`, `'no'`, and `'off'` disable it. Alias-capable policies such as
distinct fields keep string map values as aliases instead.

When this policy is null, clients may request any supported count metadata
field. When it is non-null, requested metadata is restricted to the configured
field names. Passing an empty collection blocks every optional requested count
metadata field while still returning the native `count` field.

`Restful::initialize()` calls this initializer after the query policy setup, so
count response metadata follows the same controller initialization pattern as
save, filter, search, joins, and other REST policies.

- `groupedCount`: the raw grouped count result returned by the normal count
  query.
- `bucketTotal`: the sum of the returned grouped buckets.
- `totalCount`: a second count query with the group clause removed.

Keep `bucketTotal` and `totalCount` separate. On joined grouped endpoints, one
root record can appear in more than one bucket, so the bucket sum can differ
from the ungrouped total.

## Embedded List Counts

`findAction()` and `findWithAction()` can include count metadata in the normal
list response when the client requests it with the `count` parameter. If a
controller leaves the list-count policy as null, any supported framework count
field can be requested. Controllers only need to configure a policy when they
want to restrict or block embedded counts.

```php
use Phalcon\Support\Collection;

public function initializeFindActionCountFields(): void
{
    $this->setFindActionCountFields(new Collection([
        self::REST_VIEW_COUNT,
        self::COUNT_RESPONSE_BUCKET_TOTAL,
        self::COUNT_RESPONSE_TOTAL_COUNT,
    ], false));
}
```

Request `?count=1` or `?count=true` for the standard `count` field. Request
named fields with `?count=count,totalCount`, `?count[]=count`, or enabled-map
syntax such as `?count[totalCount]=1`.

List counts use the prepared list query, including filters, search, joins,
permissions, identity conditions, bind values, and cache options. Limit and
offset are removed for count queries. Without a client `count` request, the
legacy list payload is preserved and no count query is executed. Passing an
empty collection to `setFindActionCountFields()` blocks every embedded count
field, while unsupported field names are rejected instead of becoming dynamic
response variables.

## Distinct Value Responses

`distinctAction()` returns distinct scalar values for one controller-approved
field. It is useful for facets, autocomplete controls, and dashboard filters
that must obey the same filters, joins, permissions, identity scoping, bind
values, pagination, and cache policy as the normal REST query.

The action is closed by default. Configure allowed fields in the controller
initializer:

```php
use Phalcon\Support\Collection;

public function initializeDistinctActionFields(): void
{
    $this->setDistinctActionFields(new Collection([
        'status',
        'type',
        'ownerEmail' => 'Owner.email',
    ], false));
}
```

List entries expose and query the same field. Map entries expose a stable public
field name and query a model or joined-model alias internally. The endpoint uses
the `field` request parameter:

```http
GET /api/project/distinct?field=status
GET /api/project/distinct?field=ownerEmail
```

Successful responses include:

- `data`: the returned distinct values.
- `field`: the public field requested by the client.
- `count`: the number of returned values.

Do not default this policy to all filter fields. A field can be safe to filter
by without being safe or useful to enumerate publicly.

## Rest vs Restful

Use `PhalconKit\Mvc\Controller\Rest` for custom JSON endpoints such as health
checks, webhooks, dashboards, and workflows that are not plain model resources.

Use the app API base controller backed by `Restful` for normal CRUD/query
resources. Do not extend the model-backed controller just to return JSON.
