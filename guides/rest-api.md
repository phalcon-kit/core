# REST APIs

PhalconKit REST controllers are model-backed and convention-driven. Controllers
usually configure which fields can be saved, filtered, searched, exposed, and
eager loaded.

Official Phalcon references:

- Controllers: https://docs.phalcon.io/5.13/controllers/
- Request: https://docs.phalcon.io/5.13/request/
- Response: https://docs.phalcon.io/5.13/response/
- PHQL: https://docs.phalcon.io/5.13/db-phql/

## Rest vs Restful

Use `PhalconKit\Mvc\Controller\Rest` for custom JSON endpoints that do not need
the model-backed query/action stack.

Use `PhalconKit\Mvc\Controller\Restful` through the app API base controller for
model resources that need standard find, find-with, find-first, save, delete,
restore, aggregate, export, filter, search, permission, and eager-loading
behavior.

Do not extend the model-backed controller just to return JSON.

## Controller Example

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
            'description',
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
            'createdAt',
            'updatedAt',
            'deleted',
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

## Controller Checklist

For each resource, decide these independently:

- save fields
- filter fields
- search fields
- expose fields
- map fields
- eager-loaded relations
- joins and dynamic joins
- permission conditions
- default order, limit, and max limit
- exposer or transformer output

A field can be filterable but not writable, writable but not exposed, or
exposed only through a transformer.

## Exposers And Transformers

The exposer system is easy to use and works well for straightforward model
output. Transformers are better when response shape, nested resources, and
performance need tighter control.

Use transformers for complex API resources and exposers for simpler CRUD
surfaces.

Transformer-backed output is usually the better choice when:

- relation graphs are deep
- a list endpoint must be fast
- output names differ from model property names
- nested resources need conditional includes
- external clients depend on a stable response contract

## Query Features

REST controllers can compose:

- search fields
- filter fields
- save fields
- expose fields
- map fields
- joins
- dynamic joins
- eager loading
- permission conditions
- soft-delete conditions
- group/order/limit/offset handling

These traits let app controllers keep the resource-specific rules close to the
resource, without rewriting query plumbing for each endpoint.

## Joins And Dynamic Joins

Use static joins when a resource always needs a relation for filtering, sorting,
or permission checks:

```php
public function initializeJoins(): void
{
    $this->setJoins(new Collection([
        'Project' => [
            \App\Models\Project::class,
            '[' . $this->getModelName() . '].[projectId] = [Project].[id]',
            'Project',
            'left',
        ],
    ]));
}
```

Use dynamic joins for filter/search paths that should join only when a client
uses the related field. This keeps normal list requests lighter.

## Transformer-Backed Action Example

For heavy resources, use the controller Fractal helpers directly and keep the
response contract in transformers:

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

This is useful when the default exposer output is too broad, too nested, or not
stable enough for an external client.

## Permission Conditions

Controllers can add row-level restrictions based on the current identity:

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    $this->getPermissionConditions()->set(
        'projectId',
        $this->getProjectIdPermissionCondition('projectId')
    );
}
```

Permission behavior is usually paired with config-defined feature/role policy.

## Advanced Filters

For complex resources, keep advanced filter semantics isolated in a trait or
private methods. Build condition blocks with unique bind keys and explicit bind
types. Prefer `EXISTS` subqueries for set logic when joins would multiply rows.

Use `setWith()` for data that must be returned, and joins/dynamic joins for data
that must be queried.

Example condition block:

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
