# Models And Eager Loading

PhalconKit models build on `Phalcon\Mvc\Model` and add generated model layers,
relationship-aware assignment, model behaviors, and batch eager loading.

Official Phalcon references:

- Models: https://docs.phalcon.io/5.14/db-models/
- Relationships: https://docs.phalcon.io/5.14/db-models-relationships/
- Behaviors: https://docs.phalcon.io/5.14/db-models-behaviors/
- Model validation: https://docs.phalcon.io/5.14/db-models-validation/

## Generated And Concrete Layers

Generated abstract models carry schema knowledge:

- properties and comments
- getters and setters
- column maps
- default relationships
- default validations
- generated interfaces
- enum classes where supported by the database

Concrete models carry application behavior:

```php
<?php

namespace App\Models;

final class Project extends Abstracts\ProjectAbstract
{
    public function isOpen(): bool
    {
        return !$this->isDeleted() && $this->getStatus() === 'open';
    }
}
```

When the schema changes, regenerate the abstract layer and review concrete
models for new domain rules.

## Relationship Payloads

Generated relationship aliases are used by REST save payloads and eager loading.
Typical alias shapes are:

- `UserEntity` for a single related model.
- `UserList` for a one-to-many or many-to-many list.
- `UserNode` for join/node-table records.

Controllers can allow relation writes through nested `initializeSaveFields()`
configuration:

```php
$this->setSaveFields(new Collection([
    'label',
    'usernode' => [
        'userId',
        'type',
        'deleted',
    ],
]));
```

Keep relation payloads explicit. Do not expose every nested field just because a
relationship exists.

## Strict Relationship Assignment

Relationship assignment is permissive by default for backward compatibility.
`assignRelated()` receives the full model payload before Phalcon assigns scalar
columns, so unknown scalar keys must still pass through native model assignment.

Enable strict relationship assignment on models or resource flows where the
payload has already been normalized and relation aliases are expected to be
exact:

```php
$project->setStrictRelatedAssignment(true);
$project->assign([
    'label' => 'Portal',
    'UserNode' => [
        ['userId' => 10, 'type' => 'owner'],
    ],
], [
    'label',
    'UserNode' => ['userId', 'type'],
]);
```

When strict mode is enabled, PhalconKit throws a scoped exception for
relationship-specific mistakes:

- a real relation alias is blocked by the assignment whitelist
- an unknown complex payload looks like a relation but is not a mapped model
  column
- a known relation receives an unsupported value or list item

Strict mode also follows nested relation assignment. If a parent relation
payload creates or updates a related PhalconKit model, that child receives the
same strict setting before its own nested `assign()` call runs.

Strict relationship assignment does not replace column validation, model
validation, or REST save-field policies. It is a guard for nested relation
payloads, not a general "reject every unknown scalar field" mode.

## Eager Loading

Use eager loading when a response or workflow needs related data. This avoids
lazy-loading loops and keeps relation graphs visible at the query boundary.

Avoid this pattern in list endpoints:

```php
$projects = Project::find(['limit' => 25]);

foreach ($projects as $project) {
    foreach ($project->getUserNode() as $userNode) {
        $user = $userNode->getUserEntity();
    }
}
```

Each relation access can trigger more database work. Load the graph once:

Model-level examples:

```php
$projects = Project::findWith([
    'UserNode.UserEntity',
    'CategoryList',
], [
    'conditions' => 'deleted <> 1',
]);

$project = Project::findFirstWith([
    'UserNode.UserEntity',
], [
    'conditions' => 'id = :id:',
    'bind' => ['id' => $id],
]);
```

Controller-level examples:

```php
public function initializeWith(): void
{
    $this->setWith(new Collection([
        'UserNode.UserEntity',
        'CategoryList',
    ]));
}
```

Use relation-level query builders when a relation needs extra constraints,
ordering, or limits. Keep expensive relation graphs out of list requests unless
the UI really needs them.

## List vs Detail Graphs

Use smaller graphs for list screens and richer graphs for detail screens:

```php
final class ProjectReadService
{
    public function listOpenProjects(): array
    {
        return Project::findWith([
            'UserNode.UserEntity',
        ], [
            'conditions' => 'status = :status: AND deleted <> 1',
            'bind' => ['status' => 'active'],
            'limit' => 50,
            'order' => 'id DESC',
        ]);
    }

    public function getProjectDetail(int $id): ?Project
    {
        return Project::findFirstWith([
            'UserNode.UserEntity',
            'CategoryList',
            'ExclusionReasonList',
        ], [
            'conditions' => 'id = :id: AND deleted <> 1',
            'bind' => ['id' => $id],
        ]);
    }
}
```

The important part is not the service class; it is the explicit relation graph
at the query boundary.

## Custom Relationship Override

If the scaffolder cannot infer the business alias you want, add it in the
concrete model after the generated default relationships:

```php
<?php

namespace App\Models;

final class Project extends Abstracts\ProjectAbstract
{
    public function initialize(): void
    {
        parent::initialize();

        $this->hasMany(
            'id',
            ProjectUser::class,
            'projectId',
            ['alias' => 'ActiveUserNode']
        );
    }
}
```

Keep the generated relationship intact when existing controllers or
transformers depend on it. Add the new alias for the new use case.

## Model Behaviors

PhalconKit model traits and behaviors cover common persistence rules:

- UUIDs and UUIDv7 identifiers.
- Soft delete and restore fields.
- Created, updated, deleted, and restored blameable fields.
- Slug generation.
- Position/order helpers.
- Snapshot and cache support.
- Security checks against identity roles.
- Replication helpers.

Use generated defaults for schema-derived behavior and concrete models for
business-specific behavior.

## Snapshot Changed Fields

Use `getSnapshotChangedFields()` when audit logs, domain comparisons,
replication decisions, or API response metadata need to know which persisted
snapshot values differ from the model's current raw attributes.

The helper complements Phalcon's native `getChangedFields()` instead of
replacing it. Native dirty tracking still controls persistence behavior.
`getSnapshotChangedFields()` returns mapped model field names, accepts snapshots
keyed by either database columns or mapped fields, reads current values through
`readAttribute()` rather than domain getters, and falls back to native
`getChangedFields()` only when no snapshot data exists.

```php
$changedFields = $record->getSnapshotChangedFields([
    'updatedAt',
    'updatedBy',
    'updatedAs',
]);
```

The ignore list accepts either mapped model fields such as `updatedAt` or
database columns such as `updated_at`. Use it for lifecycle and bookkeeping
fields that should not appear in business-facing diffs. Nullable fields follow
PhalconKit's existing SQL `"NULL"` string convention, so nullable `"NULL"`
snapshot values compare as `null` without mutating the snapshot.

Do not use snapshot changed fields as the sole authorization context for
sensitive flows such as password reset or privileged account changes. Those
flows should pass explicit intent and authorization context through the service
or controller layer.

## Model Cache Invalidation

The model cache behavior currently uses a coarse invalidation strategy. Create,
delete, restore, and reorder events clear the shared `modelsCache` service
because they change record visibility or ordering. Save and update events clear
the shared cache when either condition is true:

- the model has no snapshot data, which covers new records and other writes
  where Phalcon cannot compare an old row snapshot
- the model has snapshot data and Phalcon reports changed or updated fields

Unchanged snapshot-aware saves and updates do not clear the cache. Session and
audit models are excluded from the default flush behavior during model
initialization so high-volume infrastructure writes do not repeatedly clear
application model query caches.

Do not depend on targeted model cache keys yet. A future granular invalidation
contract still needs explicit cache-key naming, model whitelist rules, relation
invalidation rules, and optional pre-warming semantics before the framework can
replace the shared-cache clear safely.

### Future Granular Cache Policy

Any future targeted invalidation should be opt-in and policy-driven. The
minimum safe contract should define:

- cache-key ownership: whether keys are owned by the model class, repository,
  controller query, or application service
- key format: a stable namespace, model class/source identity, query signature,
  identity/permission scope, locale/workspace scope, pagination scope, and a
  version segment so old keys can be abandoned safely
- whitelist rules: only explicitly opted-in model classes or cache groups can
  use targeted deletion; all other model writes keep using the coarse shared
  cache clear
- reverse indexes: cached query keys must be discoverable by model class,
  relation alias, and affected primary keys before the framework can delete
  only selected entries
- relation invalidation: parent and child relation caches need explicit rules
  for belongs-to, has-one, has-many, and through relations
- mutation events: create, update, delete, restore, reorder, and lifecycle
  tasks must map to the same invalidation contract
- pre-warming: any automatic cache refill must be an application callback or
  queue job, not an implicit model-event side effect

The migration path should add observation and key registration first, then
allow specific models to opt into targeted invalidation. The framework should
keep the coarse clear as the fallback whenever a policy is missing, ambiguous,
or unable to find the affected cache keys.

## Practical Rules

- Treat the database schema as the source of truth for generated model shape.
- Keep custom relationships in concrete models when the scaffolder cannot infer
  them safely.
- Prefer `findWith()` and `findFirstWith()` for known relation graphs.
- Use transformers for heavy nested API resources.
- Keep model methods focused on domain behavior, not controller formatting.
