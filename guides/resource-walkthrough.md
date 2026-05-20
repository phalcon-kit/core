# Resource Walkthrough

This walkthrough shows how a normal database-backed API resource fits together:
schema, scaffold, concrete model logic, REST controller policy, transformer
output, and permissions.

The example uses a small project-management resource:

- `project`: the main resource.
- `project_user`: assigned users and their role in the project.

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

Use migrations in real applications. Keep `resources/migrations/` as the
schema-change history and run migrations before regenerating model layers.

## 2. Regenerate Model Layers

Regenerate generated layers without overwriting concrete model files:

```shell
./vendor/bin/phalcon-kit cli scaffold run \
  --src-dir=app/ \
  --namespace=App \
  --models-extend=\\App\\Models\\AbstractModel \
  --force \
  --no-models
```

Review the generated diff. For this schema, the useful output should include:

- `ProjectAbstract` and `ProjectUserAbstract` accessors and column maps.
- uniqueness validation for `project.label`.
- uniqueness validation for `project_user(project_id, user_id, type)`.
- relation aliases inferred from `project_id` and `user_id`.
- enum classes for `status` and `type` when enum generation is enabled.

If the relationship aliases are not what the app wants, override or add
relationships in the concrete model.

## 3. Add Concrete Model Logic

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

This is the right place for state transitions, normalization, calculated
properties, extra validations, custom relationships, and lifecycle hooks.

## 4. Configure The REST Controller

The controller defines API policy. Keep save, filter, search, expose, relation,
join, and permission decisions separate.

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

This controller allows the API to write project users through `usernode`, filter
by assigned users, and restrict non-super users to projects they can access.

## 5. Use Transformers For Stable Output

Use exposers for simple CRUD surfaces. Use transformers when response shape,
includes, or performance matter.

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

The include only emits loaded relations. Pair this with `initializeWith()` or
explicit `findWith()` calls so output does not accidentally lazy-load in loops.

## 6. Configure Permissions

Keep role policy in config:

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

Feature access and row-level access are deliberately separate. The config says
which components a role may use; `initializePermissionConditions()` scopes the
records returned by the resource.

## 7. Use The Resource

Exact URLs depend on the application route configuration. With default module
routes, the action names map to the API controller actions:

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

Example create payload:

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

## 8. Review After Each Schema Change

When the schema changes, review all connected pieces:

- migration
- generated abstract model diff
- concrete model behavior
- REST save/filter/search/expose policies
- eager-loading relation graph
- transformer includes
- permission config
- row-level permission conditions
- focused tests
