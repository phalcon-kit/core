# Build Your First REST Resource

Build a public catalogue of projects with a generated model and an explicitly
configured REST controller. The read-only API below needs only the `project`
table. Add authenticated writes after the read path works.

Complete [Getting Started](getting-started.md), configure a development database,
and run the commands from your application's root.

## 1. Create The Table

Execute this SQL in your development database. In an application you deploy,
keep the same schema in an [application migration](database-migrations.md).

```sql
CREATE TABLE project (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    label VARCHAR(120) NOT NULL,
    status ENUM('draft', 'active', 'archived') NOT NULL DEFAULT 'draft',
    budget INT UNSIGNED NOT NULL DEFAULT 0,
    deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    UNIQUE KEY project_label (label)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO project (label,status,budget) VALUES ('Community garden','active',1200),('Library renovation','draft',3000),('River cleanup','active',800);
```

The examples below use these three rows. `deleted` supports Core's default
soft-delete condition. `budget` is an integer amount in this example; choose an
explicit currency/unit convention for your own application.

## 2. Generate The Model

```shell
./scripts/generate-models.sh --table=project
```

PowerShell:

```powershell
./scripts/generate-models.ps1 --table=project
```

This creates `Project.php`, `Abstracts/ProjectAbstract.php`, their interfaces,
and `Enums/ProjectStatus.php` under `src/Models/`. The generated abstract contains
column mappings, getters/setters, and schema-derived validation. Put your own
rules in the concrete `Project` class.

After changing the table, refresh generated files with:

```shell
./scripts/regenerate-models.sh --table=project
```

The regeneration wrapper uses `--no-models` to preserve concrete model code.
Run `generate-models` first when adding a new table. See
[Scaffolding](database-scaffolding.md) for relationships and boolean columns.

## 3. Add The Controller

Create `src/Modules/Api/Controllers/ProjectController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Api\Controllers;

/** Public project catalogue; write permissions are configured separately. */
class ProjectController extends AbstractController
{
    protected ?string $modelName = \App\Models\Project::class;

    public function initializeExposeFields(): void
    {
        $this->setExposeFields([false, 'id', 'label', 'status', 'budget']);
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields(['id', 'label', 'status', 'budget']);
    }

    public function initializeSaveFields(): void
    {
        $this->setSaveFields(['label', 'status', 'budget']);
    }

    public function initializeOrderFields(): void
    {
        $this->setOrderFields(['id', 'label', 'status', 'budget']);
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(['label']);
    }

    public function initializeDistinctActionFields(): void
    {
        $this->setDistinctActionFields(['status']);
    }

    /** This catalogue is public; it has no per-user owner column. */
    public function getCreatedByColumns(): array
    {
        return [];
    }
}
```

The first `false` in `setExposeFields()` hides unspecified fields. A plain list
without it **does not hide the other model fields**. This example deliberately
omits `deleted` from JSON while retaining it for query conditions.

`getCreatedByColumns()` returns an empty list because this resource is a public
catalogue. For private or tenant-owned records, define an ownership/tenant
condition instead; see [Permissions](identity-and-permissions.md#restrict-rows).
The default expects a `createdBy` model attribute, which this table does not have.

## 4. Grant Read Access

Merge these entries into `permissions.roles.everyone.components` in
`src/Config.php`, keeping the skeleton's existing entries:

```php
\App\Modules\Api\Controllers\ProjectController::class => [
    'find', 'find-first', 'count', 'distinct',
],
\App\Models\Project::class => ['find', 'count'],
```

Both entries matter: the controller permission permits the action, and the model
permission permits the database operation. No wildcard grant is needed.

## 5. List And Filter

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'filters[0][field]=status' \
  --data-urlencode 'filters[0][operator]==' \
  --data-urlencode 'filters[0][value]=active' \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'limit=20' \
  --data-urlencode 'count=1'
```

HTTP 200:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "OK",
  "code": 200,
  "response": true,
  "view": {
    "data": [
      {"id": 1, "label": "Community garden", "status": "active", "budget": 1200},
      {"id": 3, "label": "River cleanup", "status": "active", "budget": 800}
    ],
    "count": 2
  }
}
```

`count` describes all matching rows before pagination. Omit it when the client
only needs the current page. See [Filtering](rest-filtering.md) for ranges,
search, nested groups, sorting, and offsets.

## 6. Read One Record

```shell
curl 'http://127.0.0.1:8080/api/project/find-first?id=1'
```

The response has `view.data` as one object, not a list:

```json
{"id": 1, "label": "Community garden", "status": "active", "budget": 1200}
```

An unknown ID returns HTTP 404, `response: null`, and `view: []`. An empty list
query returns HTTP 200 with `view.data: []`. These cases mean different things
to clients.

## 7. Add Protected Writes

Follow [Authentication](authentication.md) to configure the identity tables,
signing keys, auth controller, and a user with an application role. For a role
named `project-editor`, add these component grants:

```php
'project-editor' => [
    'components' => [
        \App\Modules\Api\Controllers\ProjectController::class => [
            'create', 'update', 'delete',
        ],
        \App\Models\Project::class => ['find', 'create', 'update', 'delete'],
    ],
],
```

Use the access token returned by login (replace the placeholder locally):

```shell
API_TOKEN='your-access-token'
curl http://127.0.0.1:8080/api/project/create \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"label":"Playground","status":"draft","budget":500}'
```

HTTP 201, with this `view`:

```json
{
  "saved": true,
  "mode": "create",
  "data": {"id": 4, "label": "Playground", "status": "draft", "budget": 500},
  "messages": []
}
```

Repeating that label fails the generated uniqueness validator with HTTP 422.
See [Writes And Batches](rest-writes.md) for the exact error shape, updates,
partial batches, deletes, and restores. Grant only actions you intend clients
to use, and [enforce HTTP methods](rest-api.md#enforce-http-methods) for writes.

## Next Steps

- Add a `task` relationship using [Relationships](rest-relationships.md).
- Download data and build facets using [Counts And Exports](rest-aggregates.md).
- Exercise the [API scenario checklist](rest-scenarios.md) against your resource.
