# Resource Walkthrough

This walkthrough explains how the main Phalcon Kit pieces cooperate before you
copy the complete implementation in
[Build Your First REST Resource](first-rest-resource.md).

We will use a `project` resource with an owner, a status, and soft deletion.
The goal is not only to make CRUD work—it is to keep schema, business logic,
query policy, permissions, and output ownership clear.

## The Finished Flow

```text
database migration
    ↓
generated abstract model and interfaces
    ↓
app-owned Project model
    ↓
ProjectController query/save policy
    ↓
identity and permission conditions
    ↓
optional ProjectTransformer
    ↓
stable JSON response
```

Each layer has one job. When a requirement changes, this separation tells you
where the change belongs.

## 1. Start With The Schema

Define facts the database can enforce: column types, nullability, indexes,
foreign keys, and unique constraints.

```sql
CREATE TABLE project (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(160) NOT NULL,
    status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NULL,
    deleted_at DATETIME NULL,
    INDEX project_owner_id_idx (owner_id),
    CONSTRAINT project_owner_id_fk
        FOREIGN KEY (owner_id) REFERENCES user (id)
);
```

!!! note "Database-first does not mean database-only"

    The schema owns storage invariants. The model owns domain behavior. The
    controller owns HTTP/query policy. Do not force all three concerns into one
    layer.

## 2. Generate The Repetitive Layer

Run the migration, then scaffold model artifacts from the live schema:

```bash
./scripts/migration-run.sh

./scripts/generate-models.sh
```

Review the generated diff. Confirm that `owner_id` produced the expected
relationship alias and that the enum, nullable fields, and validations match
the schema.

## 3. Put Behavior In The Concrete Model

The generated abstract model may be replaced by a future scaffold run. The
concrete model is where application behavior survives regeneration.

```php
<?php

namespace App\Models;

final class Project extends Abstracts\ProjectAbstract
{
    public function activate(): void
    {
        if ($this->getStatus() !== 'draft') {
            throw new \DomainException('Only draft projects can be activated.');
        }

        $this->setStatus('active');
    }
}
```

Use a service when the operation coordinates several models, external APIs, or
transactions. Keep the model method focused on its own invariant.

## 4. Describe The HTTP Surface

The REST controller decides which fields a client may see, filter, search, and
save. A useful starting policy is deliberately small:

```php
<?php

namespace App\Modules\Api\Controllers;

final class ProjectController extends AbstractController
{
    public function initializeFilterFields(): void
    {
        $this->setFilterFields(['id', 'ownerId', 'status']);
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(['name']);
    }

    public function initializeSaveFields(): void
    {
        $this->setSaveFields(['name', 'status']);
    }

    public function initializeExposeFields(): void
    {
        $this->setExposeFields(['id', 'name', 'status', 'createdAt']);
    }
}
```

!!! warning "Do not expose every model field by default"

    Database columns are not automatically a public contract. Explicit policy
    prevents internal flags, ownership fields, hashes, and lifecycle metadata
    from leaking into responses or writes.

## 5. Add Access Policy

Feature policy answers *may this role use this resource?* Row policy answers
*which project records may this identity use?*

```php
#[\PhalconKit\Mvc\Controller\Attributes\PermissionFeature(
    'project.view',
    actions: ['find', 'find-with']
)]
#[\PhalconKit\Mvc\Controller\Attributes\PermissionFeature(
    'project.manage',
    actions: '*'
)]
final class ProjectController extends AbstractController
{
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

Adapt the condition to your application’s identity helpers. The important
boundary is that record visibility stays in query policy rather than being
filtered after records have already been loaded.

## 6. Exercise The Contract

Start with requests that prove one behavior at a time:

```http
GET /api/project?filter[status]=active&search=website
GET /api/project/find-with/42?with=OwnerEntity
POST /api/project
Content-Type: application/json

{"name":"Developer portal","status":"draft"}
```

Check more than the HTTP status:

- the response envelope is stable;
- hidden fields are absent;
- unsupported filters and save fields are rejected;
- relationship expansion occurs only when requested and allowed;
- another user cannot read or mutate the record;
- validation errors produce useful messages and a client-error status.

## 7. Stabilize Output When Needed

Expose fields work well for straightforward resources. Add a transformer when
the public representation needs renamed keys, calculated values, links, or a
stable nested structure independent of the model.

```php
final class ProjectTransformer extends \League\Fractal\TransformerAbstract
{
    public function transform(Project $project): array
    {
        return [
            'id' => $project->getId(),
            'name' => $project->getName(),
            'status' => $project->getStatus(),
            'active' => $project->getStatus() === 'active',
        ];
    }
}
```

## Your Next Iteration

Complete the full tutorial in
[Build Your First REST Resource](first-rest-resource.md), then try one extension:

- add a `ProjectMemberList` relationship and eager-load it;
- add an `archive-project` workflow action instead of exposing arbitrary status
  writes;
- add a distinct `status` endpoint for a filter UI;
- write an integration test proving owner-level isolation;
- add a transformer include for the owner summary.

The deeper references are [REST APIs](rest-api.md),
[Models And Eager Loading](models-and-eager-loading.md), and
[Identity And Permissions](identity-and-permissions.md).
