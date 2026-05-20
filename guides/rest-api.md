# REST APIs

PhalconKit REST controllers are model-backed and convention-driven. Controllers
usually configure which fields can be saved, filtered, searched, exposed, and
eager loaded.

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

## Exposers And Transformers

The exposer system is easy to use and works well for straightforward model
output. Transformers are better when response shape, nested resources, and
performance need tighter control.

Use transformers for complex API resources and exposers for simpler CRUD
surfaces.

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
