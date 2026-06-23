# PhalconKit Eager Loading

Use this reference when loading model relation graphs with `findWith()`,
`findFirstWith()`, instance `load()`, controller `initializeWith()`, or
relation-level `QueryBuilder` closures.

## Phalcon Baseline

Native Phalcon references:

- Models: https://docs.phalcon.io/5.16/db-models/
- Relationships: https://docs.phalcon.io/5.16/db-models-relationships/
- PHQL: https://docs.phalcon.io/5.16/db-phql/

PhalconKit eager loading is an extension over native Phalcon model
relationships and PHQL query building. Use native docs for relation aliases,
lazy relationship access, and query parameters; use this file for batched
relation graph loading and relation-level `QueryBuilder` constraints.

## Why It Matters

Eager loading is one of PhalconKit's main productivity features. It lets an app
describe a relation graph once, load that graph in batched queries, then expose
or transform the already-loaded models without lazy-loading loops in
controllers, WebSocket tasks, exports, or domain helpers.

Use eager loading when:

- A REST endpoint returns nested resources.
- A WebSocket broadcast needs a current model snapshot with relations.
- A domain helper needs related collections to calculate an aggregate.
- A transformer includes relations and should avoid implicit lazy queries.
- A list endpoint needs relation filters or ordering in a predictable place.

## Entry Points

Model APIs:

- `Model::findWith($with, $parameters)` returns a list of models with relations
  loaded.
- `Model::findFirstWith($with, $parameters)` returns one model or null with
  relations loaded.
- `$model->load($with)` loads relations onto an existing model.
- `Loader::fromArray($models, $with)` accepts normal lists and arrays keyed by
  id or alias, as long as every non-empty element is the same model class.
- Custom `find()` overrides used with `findWith()` must return a traversable
  `ResultsetInterface` that yields models of one concrete class, matching
  Phalcon's normal model resultsets.
- Dynamic helpers such as `findFirstWithById($with, $id)` and
  `findWithByStatus($with, $status)` call the native `findFirstBy...` or
  `findBy...` method, then load relations. Custom `findBy...` overrides follow
  the same traversable `ResultsetInterface` contract as `find()`.
- Deprecated aliases `with()` and `firstWith()` exist, but prefer
  `findWith()` and `findFirstWith()`.

Controller APIs:

- API controllers usually define a default relation graph in
  `initializeWith()` with `$this->setWith([...])`.
- `findWithAction()` and `findFirstWithAction()` use the controller's `with`
  collection and query parameters.
- Custom controller actions can call `$this->findWith($with, $find)` or
  `$this->findFirstWith($with, $find)` directly.

## Relation Graph Shape

Pass one relation graph array as the first argument, and pass normal Phalcon
find parameters as the second argument when needed.

```php
use App\Models\Event;
use App\Models\Donation;
use App\Models\Resolution;
use App\Models\Enums\ResolutionPublishedStatus;
use Phalcon\Db\Column;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

$isSuperUser = $this->identity->hasRole($this->getSuperRoles());

$publishedOnly = function (QueryBuilder $query) use ($isSuperUser) {
    $query->where('[' . Resolution::class . '].[deleted] = 0');

    if (!$isSuperUser) {
        $query->where(
            '[' . Resolution::class . '].[publishedStatus] = :statusPublished:',
            ['statusPublished' => ResolutionPublishedStatus::PUBLISHED->value],
            ['statusPublished' => Column::BIND_PARAM_STR],
        );
    }
};

$event = Event::findFirstWith([
    'LocationEntity',
    'DocumentList' => function (QueryBuilder $query) {
        $query->where('deleted = 0');
        $query->orderBy('position ASC');
    },
    'DocumentList.FileEntity',
    'EventParticipantList.ParticipantEntity.UserEntity.RoleList',
    'ResolutionList' => function (QueryBuilder $query) use ($publishedOnly) {
        $query->where('status = "original" and deleted = 0');
        $query->orderBy(
            "REGEXP_SUBSTR(number, '^[a-z,A-Z]+') ASC, " .
            "CAST(REGEXP_SUBSTR(number, '[0-9]+') AS UNSIGNED) ASC"
        );
        $publishedOnly($query);
    },
    'ResolutionList.ChildList' => $publishedOnly,
], [
    'id = :id:',
    'bind' => ['id' => $eventId],
    'bindTypes' => ['id' => Column::BIND_PARAM_INT],
]);
```

Graph rules:

- Numeric array values are relation aliases: `'LocationEntity'`.
- String keys with callable values add constraints:
  `'DocumentList' => function (QueryBuilder $query) { ... }`.
- Controller `initializeWith()` collections may also use enabled-map values,
  such as `'DocumentList' => false` or `'DocumentList' => 'off'`, to disable a
  relation after config merging. Direct model `findWith()` calls should prefer
  relation lists and callable constraints rather than enabled-map flags.
- Dotted aliases load nested relations:
  `'EventParticipantList.ParticipantEntity.UserEntity.RoleList'`.
- Constraints apply to the final relation path where they are attached.
- Relation aliases must match the aliases registered by generated or custom
  model relationships, such as `RoleList`, `UserRoleList`, or `FileEntity`.

## QueryBuilder Constraints

Relation closures receive `PhalconKit\Mvc\Model\EagerLoading\QueryBuilder`.
This builder extends Phalcon's query builder with eager-loading safeguards.

Use it for:

- Relation-level `where()` filters.
- Relation-level `orderBy()` clauses.
- Additional joins or builder options when the relation query still returns
  full related models.
- Bound values with explicit bind types.

The eager-loading `where()` method appends to existing relation conditions
instead of replacing them. This matters because the loader already adds the
relationship join or `IN` condition; controller code should not accidentally
remove that condition.

Do not use relation-level `columns()` or `distinct()`. They throw intentionally
because eager-loaded relation queries must return full model entities that can
be attached back onto the parent model.

Root finder parameters may still include custom `columns`. PhalconKit prepends
`*` for array and string column definitions before calling the native finder so
the root model keeps the keys needed for relation loading.

When conditions reference a model class or alias, prefer bracketed PHQL names:

```php
$query->where('[' . SurveyQuestion::class . '].[deleted] = 0');
```

Use bind values for dynamic input:

```php
$query->where(
    '[' . Donation::class . '].[value] >= :minimumValue:',
    ['minimumValue' => $minimumValue],
    ['minimumValue' => Column::BIND_PARAM_STR],
);
```

## How The Loader Works

The loader accepts one model, a resultset, a complex resultset, or an array of
models. It then:

1. Parses the relation graph.
2. Sorts and builds a relation tree.
3. Resolves each relation alias through Phalcon's models manager.
4. Batches parent ids and uses `IN` queries for each relation level.
5. Attaches loaded data back to each parent model using the lower-case alias
   property.
6. Uses arrays for many relations and a model or null for single relations.

Supported relation types:

- `belongsTo`
- `hasOne`
- `hasMany`
- `hasManyToMany` / `hasManyThrough`

For many-to-many relations, the loader queries the intermediate model and then
the target model. Repeated intermediate rows that point to the same target
model are de-duplicated by target key, and each parent receives only its own
target models.

Current implementation limits:

- Composite relation fields are not supported by eager loading yet. Relation
  fields, referenced fields, intermediate fields, and intermediate referenced
  fields must be strings.
- Relation queries must return full entities. Do not select partial relation
  columns.
- Per-parent limits on `hasMany` relations are not automatic. A relation
  `limit()` applies to the relation query, not separately to each parent.
- The through-relation loader currently filters intermediate rows with
  `deleted <> 1`, so verify node-table soft-delete columns when designing
  many-to-many relations.

## Controller Pattern

Define default eager loading in the resource controller:

```php
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

public function initializeWith(): void
{
    $this->setWith([
        'UserEntity',
        'CouncilEntity',
        'UnionEntity',
        'DonationList' => function (QueryBuilder $query) {
            $query->where('deleted = 0');
            $query->orderBy('id DESC');
        },
        'DonationList.UnionEntity',
        'DonationList.CouncilEntity',
    ]);
}
```

Expose or transform only what the endpoint actually needs:

- Exposers are simpler and work well for direct model arrays.
- Fractal transformers are better when response shape, conditional includes,
  or performance control matters.
- Transformers should include relations only when they are already loaded, for
  example through `hasDirtyRelatedAlias()` checks in a base transformer helper.

## Domain And WebSocket Pattern

Use model eager loading directly when a non-controller workflow needs a snapshot:

```php
use App\Models\Vote;
use Phalcon\Db\Column;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

$vote = Vote::findFirstWith([
    'VoteSubmissionList.VoteAnswerList',
    'VoteAllowedParticipantList' => function (QueryBuilder $query) {
        $query->where('deleted = 0');
    },
    'VoteAllowedParticipantList.ParticipantEntity.UserEntity',
    'VoteParticipantList' => function (QueryBuilder $query) {
        $query->where('deleted = 0');
    },
    'VoteParticipantList.ParticipantEntity.UserEntity',
], [
    'id = :id:',
    'bind' => ['id' => $id],
    'bindTypes' => ['id' => Column::BIND_PARAM_INT],
]);
```

This is the right shape for WebSocket broadcasts: the model event publishes a
small invalidation message, then the WebSocket task fetches the current model
with relations and exposes or transforms that snapshot.

## Agent Checklist

When changing eager loading:

1. Identify the exact response or domain snapshot that needs relations.
2. Use generated relationship aliases from the model abstract or custom model.
3. Put default REST relation graphs in `initializeWith()`.
4. Put action-specific relation graphs close to the custom action.
5. Add `QueryBuilder` closures for relation-level filters and ordering.
6. Keep relation queries full-entity; avoid `columns()` and `distinct()`.
7. Prefer explicit binds and bind types for dynamic values.
8. Ensure exposers or transformers match the loaded graph.
9. Avoid lazy-loading in loops.
10. Check composite-key and per-parent-limit needs before promising that eager
    loading can support them directly.
