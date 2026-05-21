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

    $this->view->setVar(
        'data',
        $this->transformCollection($projects, new ProjectTransformer())
    );

    return $this->setRestResponse(true);
}
```

Use transformers when response shape, field names, includes, or performance
need tighter control than a simple expose list.

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

## Rest vs Restful

Use `PhalconKit\Mvc\Controller\Rest` for custom JSON endpoints such as health
checks, webhooks, dashboards, and workflows that are not plain model resources.

Use the app API base controller backed by `Restful` for normal CRUD/query
resources. Do not extend the model-backed controller just to return JSON.
