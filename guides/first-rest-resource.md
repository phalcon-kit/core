# Build Your First REST Resource

This tutorial shows the main reason to use PhalconKit: go from a database
schema to a model-backed REST resource without rebuilding the same API plumbing
for every table.

The example uses two tables:

- `project`: the main API resource.
- `project_user`: users assigned to the project.

By the end, you have schema-backed models, a REST controller, nested relation
writes, eager loading, permission config, and example request/response payloads.

## 1. Create Or Migrate The Schema

Example MySQL schema:

```sql
CREATE TABLE project (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(120) NOT NULL,
    status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL,
    deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uniq_project_label (label)
);

CREATE TABLE project_user (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('leader', 'member', 'observer') NOT NULL DEFAULT 'member',
    deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL DEFAULT NULL,
    UNIQUE KEY uniq_project_user (project_id, user_id, type),
    KEY idx_project_user_project (project_id),
    KEY idx_project_user_user (user_id)
);
```

Use migrations in real applications. The database is the source of truth, and
the scaffolder reads that database to generate model structure.

## 2. Generate The Model Layer

Regenerate generated layers without overwriting concrete model files:

```shell
./vendor/bin/phalcon-kit cli scaffold run \
  --src-dir=app/ \
  --namespace=App \
  --models-extend=\\App\\Models\\AbstractModel \
  --force \
  --no-models
```

For this schema, review the generated diff for:

- `ProjectAbstract` and `ProjectUserAbstract` accessors and column maps.
- uniqueness validation for `project.label`;
- uniqueness validation for `project_user(project_id, user_id, type)`;
- relationship aliases inferred from `project_id` and `user_id`;
- enum classes for `status` and `type` when enum generation is enabled.

If a generated alias is not the one you want to expose in your API, add the
app-specific relationship in the concrete model.

## 3. Add Business Logic To The Concrete Model

Generated abstracts mirror the schema. Concrete models hold domain behavior:

```php
<?php

namespace App\Models;

final class Project extends Abstracts\ProjectAbstract
{
    public function canBeArchived(): bool
    {
        return !$this->isDeleted() && $this->getStatus() === 'active';
    }

    public function archive(): void
    {
        if (!$this->canBeArchived()) {
            $this->appendMessage(new \Phalcon\Messages\Message(
                'Project cannot be archived from its current state',
                ['status'],
                'InvalidStatus'
            ));
            return;
        }

        $this->setStatus('archived');
    }
}
```

Put state transitions, normalization, calculated properties, custom
relationships, and extra validation in concrete models.

## 4. Add The REST Controller

The controller declares API policy. It tells PhalconKit what the client can
write, filter, search, load, and access.

```php
<?php

namespace App\Modules\Api\Controllers;

use App\Models\ProjectUser;
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

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(new Collection([
            'id',
            'label',
            'status',
        ]));
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields(new Collection([
            'id',
            'label',
            'status',
            'deleted',
            'UserNode.userId',
            'UserNode.type',
        ]));
    }

    public function initializeWith(): void
    {
        $this->setWith(new Collection([
            'UserNode.UserEntity',
        ]));
    }

    public function initializeJoins(): void
    {
        $this->setJoins(new Collection([
            'UserNode' => [
                ProjectUser::class,
                '[' . $this->getModelName() . '].[id] = [UserNode].[projectId]',
                'UserNode',
                'left',
            ],
        ]));
    }

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
}
```

This controller allows nested `project_user` writes through `usernode`, filters
by assigned users, eager-loads users for detail responses, and scopes non-super
users to allowed projects.

## 5. Configure Role Policy

Feature permissions live in config:

```php
'permissions' => [
    'features' => [
        'manageProject' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => ['*'],
                \App\Models\Project::class => ['*'],
                \App\Models\ProjectUser::class => ['*'],
            ],
        ],
        'viewProject' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    'find',
                    'find-with',
                    'find-first',
                    'find-first-with',
                ],
                \App\Models\Project::class => ['find'],
                \App\Models\ProjectUser::class => ['find'],
            ],
        ],
    ],
    'roles' => [
        'admin' => [
            'features' => ['manageProject'],
        ],
        'researcher' => [
            'features' => ['viewProject'],
        ],
    ],
],
```

The config says which components a role can use. The controller's permission
conditions decide which rows that role can access.

## 6. Call The Resource

Exact URLs depend on your route config. With the default module route shape,
these actions are available:

```text
/api/project/find
/api/project/find-with
/api/project/find-first
/api/project/find-first-with
/api/project/save
/api/project/create
/api/project/update
/api/project/delete
```

Create a project:

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
    },
    {
      "userId": 11,
      "type": "member"
    }
  ]
}
```

Example success response shape:

```json
{
  "success": true,
  "data": {
    "id": 42,
    "label": "Systematic Review 2026",
    "status": "active",
    "deleted": false
  },
  "messages": []
}
```

Example validation response shape:

```json
{
  "success": false,
  "data": null,
  "messages": [
    {
      "field": "label",
      "type": "PresenceOf",
      "message": "required"
    }
  ]
}
```

Fetch projects with loaded users:

```http
GET /api/project/find-with?filter[status]=active&order[id]=desc&limit=20
```

Example response shape:

```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "label": "Systematic Review 2026",
      "status": "active",
      "usernode": [
        {
          "id": 100,
          "userId": 10,
          "type": "leader"
        }
      ]
    }
  ]
}
```

The exact envelope can be customized by the app, but the important parts are:
the controller policy defines allowed input/query fields, and eager loading
keeps relation data out of lazy-loading loops.

## 7. Use Transformers For Stable Output

Use exposers for simple CRUD surfaces. Use transformers when public clients need
a stable response contract.

```php
<?php

namespace App\Modules\Api\Transformers;

use App\Models\Project;
use League\Fractal\Resource\Collection;

final class ProjectTransformer extends AbstractModelTransformer
{
    public array $defaultIncludes = [
        'usernode',
    ];

    public function transform(?Project $project): array
    {
        if (!$project) {
            return [];
        }

        return [
            'id' => $project->getId(),
            'label' => $project->getLabel(),
            'status' => $project->getStatus(),
            'deleted' => $project->isDeleted(),
            'createdAt' => $project->getCreatedAt(),
            'updatedAt' => $project->getUpdatedAt(),
        ];
    }

    public function includeUserNode(Project $project): Collection
    {
        return $this->includeCollectionIfLoaded(
            $project,
            'usernode',
            new ProjectUserTransformer()
        );
    }
}
```

The include only emits loaded relations. Pair transformers with `findWith()` or
controller `initializeWith()` so API responses stay predictable and efficient.

## What You Got

With this setup, the resource has:

- generated model accessors, validation, and relationships;
- concrete model methods for business rules;
- REST list/detail/save behavior;
- nested relation writes through `usernode`;
- eager-loaded user information for `find-with` responses;
- transformer-ready output for stable API contracts;
- role policy in config;
- row-level project scoping in the controller.

## After Each Schema Change

Review all connected pieces:

- migration
- generated abstract model diff
- concrete model behavior
- REST save/filter/search/expose policies
- eager-loading relation graph
- transformer includes
- permission config
- row-level permission conditions
- focused tests
