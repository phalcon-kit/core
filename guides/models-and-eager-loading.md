# Models And Eager Loading

PhalconKit models extend Phalcon's ORM with schema scaffolding, relationship
assignment, eager loading, behaviors, and shared application services. Use
[Your First REST Resource](first-rest-resource.md) for a complete table/model/API.

## Generated And Concrete Layers

| File | Responsibility |
| --- | --- |
| `Models/Abstracts/ProjectAbstract.php` | Generated attributes, accessors, column map, validation, relationships |
| `Models/Abstracts/Interfaces/ProjectAbstractInterface.php` | Generated accessor contract |
| `Models/Interfaces/ProjectInterface.php` | Model interface; review generated changes |
| `Models/Enums/ProjectStatus.php` | Database enum cases |
| `Models/Project.php` | Application methods and business rules |

For the tutorial's schema:

```php
namespace App\Models;

class Project extends Abstracts\ProjectAbstract
{
    public function isActive(): bool
    {
        return !$this->isDeleted() && $this->getStatus() === 'active';
    }
}
```

Run `generate-models` for missing models, then `regenerate-models` after schema
changes. The regeneration wrapper preserves concrete model classes. Keep custom
behavior out of generated abstracts.

## Base Model Services And Initialization

Use the application bootstrap to register the ORM manager/metadata, connection,
config, helper, models resolver, modelsCache, security, and identity services.
Manually constructed containers must provide the same contracts.

Call `parent::initialize()` in a concrete model before adding behaviors or custom
relationships. Core models initialize common services and schema-aware behavior;
using a bare Phalcon container with no Core providers can fail before a query.

Model aliases are resolved by the `models` service. They do not rewrite direct
static class calls or generated relationship definitions. See
[Feature Setup](feature-contracts.md) before replacing an identity/audit model.

## Load A Relationship Graph

Using the [Project/Task schema](rest-relationships.md):

```php
use App\Models\Project;

$projects = Project::findWith(['TaskList'], [
    'conditions' => 'status = :status: AND deleted = 0',
    'bind' => ['status' => 'active'],
    'order' => 'id ASC',
    'limit' => 20,
]);

$project = Project::findFirstWith(['TaskList'], [
    'conditions' => 'id = :id: AND deleted = 0',
    'bind' => ['id' => 1],
]);
```

Core's list helper returns an array; its first helper returns a model or null.
These model-level calls do not acquire a REST controller's tenant/owner
conditions. Include your application's authorized scope explicitly in services.

Use callbacks for related-row constraints:

```php
$projects = Project::findWith([
    'TaskList' => static function (
        \PhalconKit\Mvc\Model\EagerLoading\QueryBuilder $query
    ): void {
        $query->andWhere('deleted = 0');
        $query->orderBy('id ASC');
    },
], ['limit' => 20, 'order' => 'id ASC']);
```

Add tenant/user constraints when related data is private. Batch eager-loading
limits can apply across the related query; do not assume a simple relation limit
means “N per parent” without testing it.

Native Phalcon also accepts `eager` on find/findFirst for supported graphs. Core
mirrors native loaded relations into its relation cache so `getRelated()`,
`relatedToArray()`, and `isRelationshipLoaded()` agree. Choose one loading API per
query instead of sending the same graph through both surfaces.

## Expose A Stable Representation

```php
$data = $project?->expose([
    false, 'id', 'label', 'status',
    'tasklist' => [false, 'id', 'label', 'status'],
]);
```

The leading false hides unspecified fields. Loading a relationship does not
make it safe to expose every child column. Use controller expose policies or an
application transformer for public representations.

## Assign Related Records

Core model `assign()` accepts nested relation data and a nested whitelist:

```php
$project->setStrictRelatedAssignment(true);
$project->assign([
    'label' => 'Community garden',
    'TaskList' => [
        ['id' => 2, 'status' => 'done'],
        ['label' => 'Water plants', 'status' => 'todo'],
    ],
], [
    'label',
    'TaskList' => ['id', 'label', 'status'],
]);

if (!$project->save()) {
    $messages = $project->getMessages();
}
```

Use an already authorized parent. Grant child model operations and enforce child
ownership. `assign()` alone does not persist or prove validation succeeded.

Strict related assignment rejects blocked relation aliases, unknown complex
relation-like payloads, and unsupported relation values/items. It propagates to
nested Core models. It is not a general “reject all unknown scalar fields” mode.

## Child Ownership And Omitted Records

Configure direct-child behavior on the concrete parent model:

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

These options can also be set globally under `model.relationship` in app config,
but that affects all models, including user-role membership. Unowned adoption
includes newly created children: setting it to false blocks new child creation
as well as attachment of persisted orphan records. Keep it enabled for the
non-null-foreign-key tutorial schema; authorize orphan adoption separately if
your schema supports it. Per-alias overrides belong under `aliases` in the
relationship options array.

The corresponding global environment variables are
`MODEL_RELATIONSHIP_ENFORCE_DIRECT_OWNERSHIP`,
`MODEL_RELATIONSHIP_ALLOW_UNOWNED_DIRECT_RELATION_ADOPTION`, and
`MODEL_RELATIONSHIP_AUTO_RESTORE_DIRECT_RELATIONS`.

The ownership guard checks stored direct-child foreign keys before assignment
so a submitted foreign key cannot hide another parent's ownership. It also
checks direct model instances. It does not authorize the parent, shared
belongs-to targets, or many-to-many targets.

Unsubmitted children are kept by default. To implement an intentional complete
replacement list, configure it explicitly on the model instance:

```php
$project->setKeepMissingRelatedAlias('TaskList', false);
```

Then an omitted existing child in a submitted replacement list can be removed.
Do not use this policy for partial edit forms. Direct soft-deleted children are
not automatically restored unless configured; automatic restoration only applies
to children already owned by the parent. Through-table relationships have their
own intermediate-node restoration behavior.

Nested relation saves are distinct from the REST batch contract. Define and
test transaction boundaries for your complete application workflow; a REST batch
is not automatically all-or-nothing.

## Custom Aliases

Add a business alias after the generated initialization:

```php
public function initialize(): void
{
    parent::initialize();
    $this->hasMany('id', \App\Models\Task::class, 'projectId', [
        'alias' => 'ProjectTasks',
    ]);
}
```

Aliases are application API vocabulary. Inspect the generated definitions when
tables have several foreign keys to the same model. Keep aliases required by
Core identity (`RoleList`, `GroupList`, `TypeList`) when mapping a User model.

## Model Behaviors

Core supports UUIDs, soft delete/restore, blameable fields, positions, slugs,
snapshots, security checks, caching, and replication helpers. Match each behavior
to real schema columns and indexes. The tutorial includes `deleted`; adding a
REST restore/reorder grant alone does not add missing storage or behavior.

The standard Core User hashes plaintext passwords in its save hook and preserves
recognized password hashes. A separately mapped application User owns its own
password workflow. Keep password attributes out of generic public APIs.

## Aggregate Values

Minimum/maximum preserve native driver values: text/datetime strings, numbers,
decimal strings, or null when no value exists. Grouped queries return resultsets;
a cancelled Core before-event can return false. Handle these cases before
converting values. See [REST aggregates](rest-aggregates.md).

## Snapshots And Caches

`getSnapshotChangedFields()` compares persisted snapshots with raw mapped
attributes. It accepts column or mapped-field names in its ignore list:

```php
$changed = $record->getSnapshotChangedFields(['updatedAt', 'updatedBy']);
```

It complements native dirty tracking, which still controls persistence. Without
a snapshot it falls back to native changed fields. Do not use a snapshot diff as
the sole authorization context for password or privileged account changes.

Default model cache invalidation clears the shared `modelsCache` on visibility/
ordering changes, and on saves/updates with changes or no usable snapshot.
Unchanged snapshot-aware saves do not clear it. Session/audit models are excluded
from default flush behavior. Do not assume per-model targeted cache eviction;
use application-owned cache keys/invalidation for specialized caches.
