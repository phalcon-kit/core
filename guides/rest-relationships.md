# Relationships In REST APIs

Relationship loading, filtering, writing, and JSON exposure are separate
policies. Configuring one does not automatically grant the others.

## Add Tasks To The Project Example

Add this table and sample data to the tutorial's development database:

```sql
CREATE TABLE task (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    project_id INT UNSIGNED NOT NULL,
    label VARCHAR(120) NOT NULL,
    status ENUM('todo', 'done') NOT NULL DEFAULT 'todo',
    deleted TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (project_id) REFERENCES project(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO task (project_id, label, status) VALUES
    (1, 'Prepare soil', 'done'),
    (1, 'Plant trees', 'todo'),
    (3, 'Collect bags', 'todo');
```

Generate the new model and refresh the parent relationship definition:

```shell
./scripts/generate-models.sh --table=project,task
./scripts/regenerate-models.sh --table=project,task
```

The generated Project abstract has `TaskList`, with `id` mapped to Task's
`projectId`. Inspect generated aliases after scaffolding: actual foreign keys,
column maps, and multiple links to the same table determine their names.

Grant `App\Models\Task::class => ['find']` to the read role and add
`find-with`/`find-first-with` to the Project controller grants and method map.

## Load A Controlled Graph

Add these policies to `ProjectController`:

```php
public function initializeWith(): void
{
    $this->setWith([
        'TaskList' => static function (
            \PhalconKit\Mvc\Model\EagerLoading\QueryBuilder $query
        ): void {
            $query->andWhere('deleted = 0');
            $query->orderBy('id ASC');
        },
    ]);
}

public function initializeExposeFields(): void
{
    $this->setExposeFields([
        false, 'id', 'label', 'status', 'budget',
        'tasklist' => [false, 'id', 'label', 'status'],
    ]);
}
```

Request:

```shell
curl --get http://127.0.0.1:8080/api/project/find-first-with \
  --data-urlencode 'id=1' \
  --data-urlencode 'with=TaskList'
```

HTTP 200, `view.data`:

```json
{
  "id": 1,
  "label": "Community garden",
  "status": "active",
  "budget": 1200,
  "tasklist": [
    {"id": 1, "label": "Prepare soil", "status": "done"},
    {"id": 2, "label": "Plant trees", "status": "todo"}
  ]
}
```

Relation definitions use `TaskList`; exposed cached relation keys use lowercase
`tasklist`. Root column-map names such as `projectId` retain their model spelling.
Keep request aliases and JSON response names distinct in client types.

The callback here excludes deleted children. For a private graph, constrain
related rows by tenant/ownership too. A root controller's row condition does not
automatically become a constraint on every related query.

## Select Relationships From The Client

| Input to a `-with` action | Meaning |
| --- | --- |
| No `with` parameter | Use the controller's default graph |
| `with=TaskList` | Select that allowed path |
| `with[]=TaskList` | List form of the same selection |
| `with[TaskList]=1` | Enabled-map form |
| `with=0`, `with=false`, or empty `with` | Load no relationships |
| Unknown path | 403 |
| `with=true` | Invalid selector, 400 |

For a configured path such as `TaskList.AssigneeEntity`, clients may select the
whole path or its parent `TaskList`. Parent callbacks remain applied when a
nested subset is selected. Both relation paths must exist in your model graph.

Plain `find`/`find-first` do not switch to eager loading because `with` is present.
Use the `-with` action. Successful save responses use the configured graph;
they do not use the read action's client-selected subset.

## Filter On Related Records

Declare both a join path and the allowed fields. For the public task example:

```php
public function initializeDynamicJoins(): void
{
    $this->setDynamicJoins([
        'TaskList' => [
            \App\Models\Task::class,
            '[TaskList].[projectId] = [App\\Models\\Project].[id]'
                . ' AND [TaskList].[deleted] = 0',
        ],
    ]);
}

public function initializeFilterFields(): void
{
    $this->setFilterFields([
        'id', 'label', 'status', 'budget',
        'TaskList.label', 'TaskList.status',
    ]);
}
```

These expressions are trusted server configuration. A private application must
add its related-row authorization constraints to the join/existence scope.

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'filters[0][field]=TaskList.label' \
  --data-urlencode 'filters[0][operator]=contains' \
  --data-urlencode 'filters[0][value]=soil'
```

Result: project 1. Related text predicates use correlated EXISTS. A negative
text predicate uses NOT EXISTS: `does not contain soil` returns projects with
**no qualifying task containing soil**, including projects without tasks.
It is not “a project has at least one different task.”

Scalar predicates normally operate on joined rows. `subquery: true` requests
existence semantics explicitly. For example:

```json
{
  "filters": [
    {"field":"TaskList.status","operator":"=","value":"todo","subquery":true},
    {"field":"TaskList.label","operator":"contains","value":"trees"}
  ]
}
```

AND predicates on the same relation scope and existential polarity coalesce so
one qualifying child must satisfy them together. Here project 1 has a todo task
whose label contains trees. A done task and a different trees task do not satisfy
a same-child requirement.

To deliberately ask about independent children, use scoped aliases such as
`TaskList[done].status` and `TaskList[pending].status`. Permit the exact requested
field shapes in your filter policy and test each scope. These scopes create
separate joins/correlations; they are not array indexes in returned JSON.

With `subquery: true`, related `is empty` means no qualifying nonempty child
exists; related `is not empty` means at least one nonempty child exists. Without
that flag, unary predicates retain joined-row semantics. Test no-child, null,
empty, matching-child, and mixed-child cases separately.

`joins[TaskList]` can supply filter nodes applied to the generated join scope.
The same identifier/operator policies apply; use server conditions for security
constraints that clients must never remove.

## Write Children

Allow only the relation fields the caller may modify:

```php
public function initializeSaveFields(): void
{
    $this->setSaveFields([
        'label', 'status', 'budget',
        'tasklist' => ['id', 'label', 'status'],
    ]);
}
```

Grant the necessary Task model `create`/`update` permissions to the write role.
Enable direct-child ownership checks in the concrete `App\Models\Project` model:

```php
/** Configure the relationship policy for every instance of this model. */
public function initializeOptions(): void
{
    parent::initializeOptions();
    $this->setRelationshipOptions([
        'enforceDirectOwnership' => true,
        'allowUnownedDirectRelationAdoption' => true,
        'autoRestoreDirectRelations' => false,
    ]);
}
```

Use `initializeOptions()` for this instance option; Phalcon's `initialize()`
runs once per model class and does not configure every later instance.

Here unowned adoption is enabled because newly constructed children have no
parent foreign key until the relation save assigns it. Setting it to false also
rejects those new children. The example's non-null `task.project_id` prevents
persisted orphan rows; if your schema permits orphans, authorize their adoption
separately. Keep `projectId` out of the writable child fields.

To return a useful client error for rejected relationship input, add this to
`ProjectController`:

```php
/** Convert rejected nested input into an HTTP validation error. */
public function save(?string $forceMode = null): array
{
    try {
        return parent::save($forceMode);
    } catch (\PhalconKit\Exception\InvalidArgumentException $exception) {
        if ($exception->getCode() !== 400) {
            throw $exception;
        }
        throw new \PhalconKit\Exception\HttpException(
            'Invalid related record assignment.', 400, $exception
        );
    }
}
```

The model throws an argument exception for an ownership violation. The default
HTTP dispatcher only maps `HttpException` codes to client statuses; without this
adapter, an uncaught argument exception is a server error. Use a generic public
message and keep internal model details in application logs.

Update one child and add another:

```shell
curl -X PATCH http://127.0.0.1:8080/api/project/update \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":1,"tasklist":[{"id":2,"status":"done"},{"label":"Water plants","status":"todo"}]}'
```

On success, `view.saved` is true. With the graph/exposure policies above,
`view.data.tasklist` includes the current children and their generated IDs.
Unsubmitted children remain by default, so a submitted empty list changes
nothing, just like omitting `tasklist`. With `keepMissingRelated` set to false
for the relation (see [Models And Eager Loading](models-and-eager-loading.md)),
the submitted list replaces the children: existing children missing from it
are deleted, and an empty list deletes them all. Omitting the relation from the
payload still leaves the children untouched. Do not use that policy for a
partial edit form.

A `false` item in a submitted list, such as `"tasklist":[false]`, is a legacy
sentinel that switches that replacement on for the request; `true` switches it
off. Removal deletes child records through the child model, so it requires the
Task model's `delete` permission. Do not grant `delete` on the child model to a
role that should only add or edit children through the parent.

Never accept a client-supplied parent/tenant foreign key merely because it exists
in the schema. Direct-child ownership guards do not authorize unrelated parents,
belongs-to targets, or shared many-to-many targets; check those in your own policy.
For stricter relation-shape validation and per-relation options, see
[Models And Eager Loading](models-and-eager-loading.md).

Submitting task ID 3 under project 1 is rejected with HTTP 400 by the adapter
above; task 3 remains attached to project 3. Check that invariant in tests as
well as checking the response status.

## Link Shared Records

A many-to-many relation (`hasManyToMany` through a link table) connects the
parent to shared records. Suppose Project has a `TagList` alias through a
`project_tag` link table. Each submitted item names a tag:

```shell
curl -X PATCH http://127.0.0.1:8080/api/project/update \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":1,"taglist":[2,3]}'
```

- A number is the tag's key, so `[2, 3]` links existing tags 2 and 3. A link
  that already exists is kept, not duplicated, and no tag record is created.
- An object such as `{"id":1,"name":"garden"}` also saves the tag's fields.
  Tags are shared, so the change is visible to every project linked to them.
  To let a caller link tags without editing them, allow only the key in the
  save fields: `'taglist' => ['id']`.
- Links missing from the list are kept by default. With `keepMissingRelated`
  false for the alias, the list is the complete set: other links are removed
  (soft-deleted when the link model has a `deleted` column), the tag records
  are never deleted, and submitting a tag again restores its link.

Grant the permissions both models need: linking finds, creates, and removes
link rows, and saves each submitted tag.

## What A Nested Save Commits

Saving a parent with related records runs in one database transaction: the
parent, its children, and its links commit together. When a related record
fails to save, for example a child that fails validation, the save returns the
validation messages and rolls back the parent change and any children already
saved in that request.

The transaction covers one parent graph. Records in a REST batch are saved one
by one, so a failure in one record does not undo the others; see
[Writes And Batches](rest-writes.md).
