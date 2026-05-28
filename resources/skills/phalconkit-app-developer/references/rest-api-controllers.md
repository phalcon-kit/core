# REST API Controller Patterns

Use this reference when adding or changing a PhalconKit REST resource
controller. These patterns come from real application controllers and should be
adapted to the current app's local base controller, model aliases, exposer
service, and identity roles.

## Phalcon Baseline

Native Phalcon references:

- Controllers: https://docs.phalcon.io/5.13/controllers/
- Request: https://docs.phalcon.io/5.13/request/
- Response: https://docs.phalcon.io/5.13/response/
- Models: https://docs.phalcon.io/5.13/db-models/
- PHQL: https://docs.phalcon.io/5.13/db-phql/

PhalconKit REST controllers build on native Phalcon controllers, request and
response services, models, and PHQL. Use native docs for base controller
lifecycle and this file for PhalconKit REST actions, field policies, exposers,
joins, filters, and permission conditions.

## Rest vs Restful

`PhalconKit\Mvc\Controller\Rest` is the lower-level API controller base. It
extends the normal MVC controller and adds:

- controller behaviors
- debug helpers
- Fractal support
- request parameter helpers
- JSON REST response helpers such as `setRestResponse()`

For transformer-backed output, also read `transformers.md`.

Use `Rest` when the endpoint is custom workflow code and should not inherit the
model-backed REST action/query stack.

```php
namespace App\Modules\Api\Controllers;

use PhalconKit\Mvc\Controller\Rest;

final class HealthController extends Rest
{
    public function indexAction()
    {
        return $this->setRestResponse([
            'ok' => true,
            'service' => 'api',
        ]);
    }
}
```

`PhalconKit\Mvc\Controller\Restful` extends `Rest` and adds the model-backed
resource stack:

- standard REST actions such as find, find-first, save, delete, restore,
  export, count, sum, min, max, average, and distinct
- model lookup from the controller name
- query initialization and compilation
- save/filter/search/expose field policies
- eager loading with `initializeWith()`
- joins with `initializeJoins()`
- permission, identity, filter, search, and soft-delete conditions

Use `Restful` for CRUD/query resources backed by a Phalcon model. In app API
modules, prefer extending the app's module base controller, commonly
`App\Modules\Api\Controllers\AbstractController`, when it already extends the
module API controller.

```php
namespace App\Modules\Api\Controllers;

use Phalcon\Support\Collection;

final class InvoiceController extends AbstractController
{
    public function initializeSaveFields(): void
    {
        $this->setSaveFields(new Collection([
            'customerId',
            'number',
            'date',
            'status',
        ]));
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(new Collection([
            'number',
            'status',
        ]));
    }
}
```

Decision rule:

- Choose `Rest` for custom endpoints like health checks, webhooks, dashboards,
  imports, exports with custom orchestration, and non-CRUD actions.
- Choose `Restful` for normal model resources where clients should use the
  framework's standard list/detail/save/delete/query behavior.
- Do not extend `Restful` just to return JSON; that opts into query/model
  lifecycle behavior.
- Do not reimplement CRUD/query actions in `Rest` when the resource fits the
  `Restful` model-backed pattern.

## App API Base Controller

Real apps should usually add their own API abstract controller between
`PhalconKit\Modules\Api\Controller` and concrete resource controllers. Use it
for shared app concerns such as exposer collections, shared permission
conditions, and response-shape tweaks.

Initialize app dependencies before calling `parent::initialize()` when the
parent initialization may call controller hooks that depend on those
dependencies. For example, `initializeExposeFields()` can use `$this->exposers`,
so the base controller prepares it first.

```php
namespace App\Modules\Api\Controllers;

use App\Config\Exposers;
use Phalcon\Db\Column;
use Phalcon\Support\Collection;
use PhalconKit\Modules\Api\Controller;

abstract class AbstractController extends Controller
{
    public Collection $exposers;

    public function initialize()
    {
        $this->exposers = new Exposers();

        parent::initialize();
    }

    public function initializeFindActionCountFields(): void
    {
        $this->setFindActionCountFields(new Collection([
            self::REST_VIEW_COUNT,
        ], false));
    }

    public function filterStatusOrAdminCondition(): array
    {
        if ($this->identity->hasRole($this->getSuperRoles())) {
            return [];
        }

        return [
            'status <> :statusDraft:',
            'bind' => ['statusDraft' => 'draft'],
            'bindTypes' => ['statusDraft' => Column::BIND_PARAM_STR],
        ];
    }
}
```

Use this base-controller pattern intentionally:

- Put cross-resource behavior here only when most API resources should inherit
  it.
- Keep resource-specific field policies, relation graphs, joins, and row-level
  permission methods in the concrete controller.
- Preserve the framework action signatures when overriding standard REST
  actions.
- Prefer `initializeFindActionCountFields()` over overriding `findAction()` just
  to add list counts.
- Avoid unused imports in copied examples; keep the base controller clean
  because every resource inherits it.

## Response Contract

`setRestResponse()` keeps the stable PhalconKit JSON envelope: `timestamp`,
`status`, `code`, `response`, `view`, and optional `debug`. Custom actions should
set response data through the named helpers:

```php
public function dashboardAction(): ResponseInterface
{
    $this->setRestViewVars([
        self::REST_VIEW_DATA => $this->listExpose($this->find()),
        self::REST_VIEW_COUNT => $this->count(),
    ]);

    return $this->setRestResponse(true);
}
```

Use `REST_VIEW_DATA`, `REST_VIEW_MESSAGES`, `REST_VIEW_COUNT`,
`REST_VIEW_FIELD`, `REST_VIEW_SUM`, `REST_VIEW_AVERAGE`, `REST_VIEW_MINIMUM`,
`REST_VIEW_MAXIMUM`, `REST_VIEW_SAVED`, `REST_VIEW_RESULTS`,
`REST_VIEW_STATS`, `REST_VIEW_DELETED`, `REST_VIEW_RESTORED`, and
`REST_VIEW_REORDERED` instead of repeating string literals in app controllers.

## Count Actions

`countAction()` exposes the native Phalcon count result as `count`. If the
query has a `group` clause, `count` can be a grouped result instead of a scalar
total.

Controllers that need count metadata can opt into explicit extra fields:

```php
use Phalcon\Support\Collection;

public function initializeCountActionResponseFields(): void
{
    $this->setCountActionResponseFields(new Collection([
        self::COUNT_RESPONSE_GROUPED_COUNT,
        self::COUNT_RESPONSE_BUCKET_TOTAL,
        self::COUNT_RESPONSE_TOTAL_COUNT,
    ], false));
}
```

`Restful::initialize()` calls this initializer with the rest of the REST setup,
so count response metadata should be configured in the controller lifecycle
instead of inside `countAction()`.

- `groupedCount` is the raw grouped count result.
- `bucketTotal` sums the returned grouped buckets.
- `totalCount` runs a second count query with the group clause removed.

Do not present `bucketTotal` as a unique-record total. Joined grouped counts
can place one root record in more than one bucket.

## Embedded List Counts

`findAction()` and `findWithAction()` can embed count metadata when the client
requests it with the `count` parameter. By default, a null list-count policy is
unrestricted across the supported framework fields. Override
`initializeFindActionCountFields()` only when a controller must restrict or
block embedded counts.

```php
use Phalcon\Support\Collection;

public function initializeFindActionCountFields(): void
{
    $this->setFindActionCountFields(new Collection([
        self::REST_VIEW_COUNT,
        self::COUNT_RESPONSE_BUCKET_TOTAL,
        self::COUNT_RESPONSE_TOTAL_COUNT,
    ], false));
}
```

Request examples:

- `?count=1` or `?count=true` requests the standard `count` field.
- `?count=0`, `?count=false`, or omitting `count` runs no count query.
- `?count=count,totalCount` requests named fields.
- `?count[]=count&count[]=totalCount` requests named fields as a list.
- `?count[totalCount]=1` requests named fields as an enabled map.

List counts use the same prepared query as the list endpoint, so filters,
search, joins, permissions, identity conditions, binds, and cache policy stay
consistent. Limit and offset are removed for count queries. Without a client
`count` request, no count query runs and the legacy list payload is preserved.
Passing an empty collection to `setFindActionCountFields()` blocks every
embedded count field, while unsupported request names are rejected.

## Distinct Actions

`distinctAction()` exposes distinct values for one explicitly allowed field. Use
it for facets or autocomplete controls that should respect the controller's
normal filters, joins, permissions, identity conditions, binds, pagination, and
cache policy.

The default is closed. Configure fields during controller initialization:

```php
use Phalcon\Support\Collection;

public function initializeDistinctActionFields(): void
{
    $this->setDistinctActionFields(new Collection([
        'status',
        'type',
        'ownerEmail' => 'Owner.email',
    ], false));
}
```

Call it with `GET /api/project/distinct?field=status`. The response puts the
value list in `data`, the requested public field in `field`, and the returned
value count in `count`.

Use map entries when the public API field should differ from the internal query
alias. Avoid automatically reusing every filter field; high-cardinality fields
such as emails, names, tokens, or IDs are often filterable but not good public
enumeration fields.

## Controller Checklist

For each REST resource, decide these separately:

- Save policy: fields the API may write.
- Filter policy: fields the API may query/filter.
- Distinct policy: fields the API may enumerate as value lists.
- Search policy: fields included in broad text search.
- Expose policy: fields returned in responses.
- Relation graph: aliases loaded through `initializeWith()`.
- Query joins: joins used for filtering, sorting, or permission checks.
- Permission conditions: row-level access constraints for the current identity.
- Limits: `limit` and `maxLimit` caps for list endpoints.
- Transformer policy: whether this resource uses exposers, Fractal
  transformers, or a clearly separated mix by action.

Do not collapse these into one field list. A field can be filterable but not
exposed, exposed but not writable, or writable only inside a nested relation
payload.

## Field Policy Methods

Use `Phalcon\Support\Collection` and override the narrow initializer for each
policy.

```php
use Phalcon\Support\Collection;

final class FundraisingController extends AbstractController
{
    protected ?int $maxLimit = 1000;

    public function initializeSaveFields(): void
    {
        $this->setSaveFields(new Collection([
            'eventId',
            'label',
            'goalValue',
            'date',
        ]));
    }

    public function initializeFilterFields(): void
    {
        $this->setFilterFields(new Collection([
            'id',
            'uuid',
            'eventId',
            'label',
            'goalValue',
            'currentValue',
            'date',
            'deleted',
            'createdAt',
            'updatedAt',
            'DonationList' => [
                'participantId',
                'value',
                'firstName',
                'lastName',
                'date',
                'deleted',
            ],
        ]));
    }

    public function initializeSearchFields(): void
    {
        $this->setSearchFields(new Collection([
            'label',
            'goalValue',
            'currentValue',
            'date',
        ]));
    }
}
```

Guidelines:

- Keep `initializeSaveFields()` as the smallest safe write whitelist.
- Include nested relation arrays in save fields only when the app's REST save
  flow supports relation payloads for that relation.
- Use `initializeFilterFields()` for client-queryable fields, including nested
  relation fields when the app allows relation filters. Leaving filter fields
  null preserves unrestricted legacy filtering; an empty collection closes
  filtering entirely.
- Use `initializeSearchFields()` for broad text search; keep it smaller than
  filter fields. Filter/search enabled-map values are boolean-normalized, so
  config values like `off`, `false`, and `0` disable keys.
- Set `limit` or `maxLimit` explicitly on high-volume resources.

For very large import/export/review resources, override `initializeLimit()` only
when the endpoint is intentionally unbounded and the app has accepted the
runtime cost:

```php
use PhalconKit\Support\Utils;

public function initializeLimit(): void
{
    Utils::setUnlimitedRuntime();
    $this->setMaxLimit(-1);
    $this->setLimit(-1);

    parent::initializeLimit();
}
```

Do not use unlimited limits on normal list endpoints. Prefer pagination unless
the endpoint is an operator workflow, export, or controlled batch process.

## Model-Derived Save Fields

For deeply nested resources with many generated columns, apps can derive save
fields from models and then attach relation graphs.

```php
public function initializeSaveFields(): void
{
    $question = $this->getSavableColumnsFromModel(new SurveyQuestion());
    $question['surveyaiquestionlist'] = $this->getSavableColumnsFromModel(new SurveyAiQuestion());
    $question['surveychoicelist'] = $this->getSavableColumnsFromModel(new SurveyChoice());

    $group = $this->getSavableColumnsFromModel(new SurveyGroup());
    $group['surveyquestionlist'] = $question;

    $survey = $this->getSavableColumnsFromModel(new Survey());
    $survey['surveygrouplist'] = $group;
    $survey['surveyquestionlist'] = $question;

    $this->setSaveFields(new Collection($survey));
}
```

Use this pattern when generated model metadata is a better source of truth than
a hand-maintained whitelist. Still remove dangerous fields and nested relations
that clients must not write.

## Exposers

Use centralized exposers when the app has an exposer collection or service.
They make response shapes reusable across controllers and nested relations.
Exposers are the easiest response-shaping path for simple CRUD resources. For
complex or performance-sensitive responses, prefer Fractal transformers; see
`transformers.md`.

The PhalconKit exposer supports root defaults:

- `[false, 'id', 'label']` means deny by default, expose listed fields.
- `[true, 'password' => false]` means allow by default, hide listed fields.
- Nested arrays define relation/object exposure rules.

```php
use Phalcon\Support\Collection;

final class Exposers extends Collection
{
    public function __construct(array $data = [], bool $insensitive = false)
    {
        parent::__construct($data, $insensitive);

        $this->set('Donation', [
            false,
            'id',
            'uuid',
            'participantId',
            'value',
            'firstName',
            'lastName',
            'date',
            'UnionEntity' => $this->get('Union'),
            'CouncilEntity' => $this->get('Council'),
        ]);

        $this->set('Fundraising', [
            false,
            'id',
            'uuid',
            'label',
            'goalValue',
            'currentValue',
            'date',
            'DonationList' => $this->get('Donation'),
        ]);
    }
}
```

Controller usage:

```php
public function initializeExposeFields(): void
{
    $this->setExposeFields(new Collection(
        $this->exposers->get('Fundraising')
    ));
}
```

For role-aware output, merge extra relation exposure only when the identity is
allowed to see it.

```php
public function initializeExposeFields(): void
{
    $participantFields = [];

    if ($this->identity->hasRole(['admin'])) {
        $participantFields = [
            'EventParticipantList' => $this->exposers->get('EventParticipant'),
        ];
    }

    $this->setExposeFields(new Collection(array_merge(
        $this->exposers->get('Event'),
        $participantFields
    )));
}
```

When adding a relation to an exposer, also check whether the controller should
load that relation in `initializeWith()` for the actions that return it.

## Eager Loading With Relations

For full model-level behavior, loader internals, and `QueryBuilder` limits,
read `references/eager-loading.md`.

Use relationship aliases from generated model abstracts
(`addDefaultRelationships()`), not guessed table names. Relation aliases usually
look like `FileEntity`, `DonationList`, or
`DonationList.CouncilEntity`.

```php
use Phalcon\Db\Column;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

public function initializeWith(): void
{
    $relations = [];

    switch ($this->dispatcher->getActionName()) {
        case 'save':
        case 'find-first-with':
            $isSuperUser = $this->identity->hasRole($this->getSuperRoles());

            $publishedOnly = function (QueryBuilder $query) use ($isSuperUser) {
                $query->where('deleted = 0');

                if (!$isSuperUser) {
                    $query->where(
                        'publishedStatus = :statusPublished:',
                        ['statusPublished' => 'published'],
                        ['statusPublished' => Column::BIND_PARAM_STR],
                    );
                }
            };

            $relations = [
                'LocationEntity',
                'DocumentList' => function (QueryBuilder $query) {
                    $query->where('deleted = 0');
                    $query->orderBy('position ASC');
                },
                'DocumentList.FileEntity',
                'ResolutionList' => function (QueryBuilder $query) use ($publishedOnly) {
                    $query->where('status = "original" and deleted = 0');
                    $query->orderBy('number ASC');
                    $publishedOnly($query);
                },
                'ResolutionList.ChildList' => $publishedOnly,
                'FundraisingList' => function (QueryBuilder $query) {
                    $query->where('deleted = 0');
                },
                'FundraisingList.DonationList' => function (QueryBuilder $query) {
                    $query->where('deleted = 0');
                    $query->orderBy('id DESC');
                },
                'FundraisingList.DonationList.UnionEntity',
            ];
            break;
    }

    $relations[] = 'FileEntity';

    $this->setWith(new Collection($relations));
}
```

Guidelines:

- Gate heavy relation graphs by action name. Lists often need less than
  detail/save responses.
- Use `QueryBuilder` closures for relation-level `where()` and `orderBy()`.
- Use bind values and bind types for dynamic values.
- Keep always-needed relations outside the switch.
- Avoid loading relations only to hide them later.
- Remember that `findAction()` stays relation-free. Only `findWithAction()` and
  `findFirstWithAction()` use this response eager-load graph.

For list/detail differences, prefer a clear early return:

```php
public function initializeWith(): void
{
    if ($this->isListRequest()) {
        $this->setWith(new Collection([
            'UserNode.UserEntity',
            'CategoryList',
        ]));
        return;
    }

    $this->setWith(new Collection([
        'UserNode.UserEntity',
        'CategoryList',
        'ExclusionReasonList',
        'SurveyList',
        ...$this->getSurveyEagerLoadingDefinition('SurveyList.'),
    ]));
}
```

Keep list relation graphs small. Detail and save responses can load larger
graphs when the client needs the full editable resource.

## Request-Time Relation Selection

`findWithAction()` and `findFirstWithAction()` support a frontend `with`
parameter. If the parameter is absent, the configured `initializeWith()` graph
is loaded exactly as before. If the parameter is present, the configured graph
becomes the allow-list and only the requested subset is loaded.

Configured relation graphs accept the same enabled-map disabling convention as
other REST policies. Use value-list entries for normal relations, callable
string-key entries for relation constraints, and boolean-like false values such
as `false`, `0`, `'0'`, or `'off'` when merged config should remove a relation
from both the default graph and the request allow-list.

Supported request shapes:

- `?with=OwnerEntity,StatusEntity`
- `?with[]=OwnerEntity&with[]=StatusEntity`
- `?with[OwnerEntity.ProfileEntity]=1`
- `?with=OwnerEntity.ProfileEntity.AvatarFile`
- `?with=0`, `?with=false`, or an empty `with` parameter loads no relations.

Nested paths can be requested directly. The eager loader already resolves
required parent paths, so the client does not need to send both
`OwnerEntity.ProfileEntity` and `OwnerEntity.ProfileEntity.AvatarFile`.

Allowed subset rules:

- A configured exact path can be requested.
- A parent of a configured nested path can be requested.
- A child path that is not in the configured graph is rejected.
- Configured parent constraint callbacks are preserved when the requested child
  path needs that parent relation.

This is response eager loading only. Use dynamic joins for request-time
filtering, searching, permissions, or ordering against related models.

## Query Joins

Use `initializeJoins()` for query-level joins needed by filters, ordering, or
permission conditions. Joins do not automatically expose or load relation data;
use `initializeWith()` for response relation graphs.

```php
use App\Models\Donation;
use App\Models\Fundraising;
use Phalcon\Support\Collection;

public function initializeJoins(): void
{
    $this->setJoins(new Collection([
        [
            Donation::class,
            '[' . Fundraising::class . '].[id] = [Donation].[fundraisingId]',
            'Donation',
            'left',
        ],
    ]));
}
```

The supported join shape is `[modelClass, onSql, alias, type]`. Keyed joins are
often clearer in large controllers:

```php
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
```

The alias is used by query conditions, for example
`appendModelName('participantId', 'Donation')`. Keep alias casing consistent
with filter/search fields.

## Dynamic Joins

Use `initializeDynamicJoins()` when dotted filter fields should add joins only
when the request actually filters on that relation. This keeps normal list
queries smaller while still supporting deep filters.

```php
public function initializeDynamicJoins(): void
{
    $this->setDynamicJoins(new Collection([
        'Comment' => [
            Comment::class,
            '[' . $this->getModelName() . '].[id] = [Comment].[recordId] ' .
                'and [Comment].[deleted] <> 1',
        ],
        'RecordTag' => [
            RecordTag::class,
            '[' . $this->getModelName() . '].[id] = [RecordTag].[recordId] ' .
                'and [RecordTag].[deleted] <> 1',
        ],
        'RecordTag.Tag' => [
            Tag::class,
            '[RecordTag].[tagId] = [RecordTag.Tag].[id] ' .
                'and [RecordTag.Tag].[deleted] <> 1',
        ],
    ]));
}
```

Dynamic join rules:

- Add matching dotted names to `initializeFilterFields()`, such as
  `RecordTag.Tag.label`.
- Define every intermediate alias. If `RecordTag.Tag.label` is filterable,
  both `RecordTag` and `RecordTag.Tag` need join definitions.
- Include soft-delete constraints in join SQL when the relation should ignore
  deleted rows.
- Use dynamic joins for optional relation filters, not for relations that every
  request needs.

## Permission Conditions

Use `initializePermissionConditions()` for row-level access rules. Call the
parent first unless the controller intentionally removes default ownership
conditions.

```php
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Participant;
use Phalcon\Db\Column;

public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    if (!in_array($this->dispatcher->getActionName(), ['find', 'find-with'], true)) {
        $this->getPermissionConditions()->set(
            'publicOrParticipant',
            $this->publicOrParticipantPermissionCondition()
        );
    }
}

public function publicOrParticipantPermissionCondition(): array
{
    $publicCondition = "{$this->appendModelName('public')} = 1";

    if ($this->identity->hasRole($this->getSuperRoles())) {
        return ['true'];
    }

    if (!$this->identity->hasRole(['participant'])) {
        return [$publicCondition];
    }

    $participant = Participant::findFirst([
        'userId = :userId:',
        'bind' => ['userId' => (int)$this->identity->getUserId()],
        'bindTypes' => ['userId' => Column::BIND_PARAM_INT],
    ]);

    if (!$participant) {
        return [$publicCondition];
    }

    $participantId = $this->generateBindKey('participantId');

    return [
        $publicCondition . ' or ' .
            "{$this->appendModelName('participantId', 'EventParticipant')} = :{$participantId}:",
        'bind' => [$participantId => $participant->getId()],
        'bindTypes' => [$participantId => Column::BIND_PARAM_INT],
        'joins' => [[
            EventParticipant::class,
            '[' . Event::class . '].[id] = [EventParticipant].[eventId] ' .
                'AND [EventParticipant].[deleted] = 0',
            'EventParticipant',
            'left',
        ]],
    ];
}
```

Guidelines:

- Use `appendModelName()` for model-qualified fields.
- Use `generateBindKey()` for custom conditions to avoid bind collisions.
- Return `'bind'`, `'bindTypes'`, and `'joins'` when the condition needs them.
- Keep action exceptions explicit and narrow.
- Make the super-role path obvious.

For permission-driven behaviors that add or remove conditions from config, see
`behaviors.md`.

For tenant/project scoped apps, put the reusable condition in the app base
controller and call it from each resource:

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    if (!$this->identity->hasRole($this->getSuperRoles())) {
        $this->getPermissionConditions()->set(
            'projectId',
            $this->getProjectIdPermissionCondition('projectId')
        );
    }
}
```

Use the model's project/tenant field for the condition key. For a project
resource itself, the field may be `id`; for child resources, it is usually
`projectId`.

## Role-Aware Save Fields

Field policies can depend on the current identity. Keep the default whitelist
strict, then add privileged fields for specific roles.

```php
public function initializeSaveFields(): void
{
    $this->setSaveFields(new Collection([
        'UserEntity' => [
            'firstName',
            'lastName',
        ],
        'phone',
        'councilId',
        'unionId',
        'identification',
        'pronoun',
    ]));

    if ($this->identity->hasRole(['dev', 'admin'])) {
        $this->getSaveFields()->set('userId', true);
    }
}
```

Use this pattern for fields like `userId`, ownership columns, statuses, and
workflow flags. Do not expose privileged writes through nested arrays unless the
same role check applies to the nested relation.

## Relation Search And Joins

When filters or search fields reference related models, add matching joins.
Relation loading and joins are separate: `initializeWith()` controls returned
relations; `initializeJoins()` controls query shape.

```php
use App\Models\Council;
use App\Models\Participant;
use App\Models\Union;
use App\Models\User;
use Phalcon\Support\Collection;

public function initializeSearchFields(): void
{
    $this->setSearchFields(new Collection([
        'phone',
        'UserEntity' => [
            'firstName',
            'lastName',
            'email',
        ],
        'CouncilEntity' => [
            'label',
        ],
        'UnionEntity' => [
            'label',
        ],
    ]));
}

public function initializeJoins(): void
{
    $this->setJoins(new Collection([
        [User::class, '[' . Participant::class . '].[userId] = [UserEntity].[id]', 'UserEntity', 'left'],
        [Union::class, '[' . Participant::class . '].[unionId] = [UnionEntity].[id]', 'UnionEntity', 'left'],
        [Council::class, '[' . Participant::class . '].[councilId] = [CouncilEntity].[id]', 'CouncilEntity', 'left'],
    ]));
}
```

Use aliases consistently across search/filter fields, joins, permission
conditions, and exposers.

## Nested Save Graphs

Nested save arrays are useful for ordered child collections such as surveys,
votes, documents, and fundraising donations.

```php
public function initializeSaveFields(): void
{
    $this->setSaveFields(new Collection([
        'eventId',
        'label',
        'status',
        'SurveySectionList' => [
            'label',
            'description',
            'position',
            'deleted',
            'SurveyQuestionList' => [
                'label',
                'type',
                'max',
                'required',
                'position',
                'deleted',
                'SurveyQuestionChoiceList' => [
                    'label',
                    'description',
                    'position',
                    'other',
                    'deleted',
                ],
            ],
        ],
    ]));
}
```

Pair nested writes with relation-level validation where business rules matter:
survey question choices must belong to the question, vote answers must match the
current vote iteration, and participant-owned submissions should force
`participantId` from identity unless an admin role is editing.

## Before-Assign Sanitization

Use save hooks or behaviors to remove relation payloads that are display-only or
managed by another path before `assign()` runs.

```php
use Phalcon\Mvc\ModelInterface;

public function beforeAssign(
    ModelInterface &$entity,
    array &$post,
    ?array &$whiteList,
    ?array &$columnMap
): void {
    array_unset_recursive($post, ['userentity']);
}
```

Keep this hook narrow. It should strip unsafe payload keys or normalize data,
not implement the whole save workflow. If the app relies on this method, verify
that the base controller or behavior wiring attaches it to `rest:beforeAssign`.

## Custom Resource Actions

Keep custom actions thin and use the same response helpers as standard REST
actions.

```php
use App\Models\Version;
use Phalcon\Db\Column;
use Phalcon\Http\ResponseInterface;

public function checkAction(): ResponseInterface
{
    $clientVersion = (string)$this->getParam('version');

    $latest = Version::findFirst([
        'deleted <> 1',
        'order' => 'id DESC',
    ]);

    if (!$latest) {
        return $this->setRestErrorResponse(404, 'No version found');
    }

    $client = Version::findFirst([
        'version = :version:',
        'bind' => ['version' => $clientVersion],
        'bindTypes' => ['version' => Column::BIND_PARAM_STR],
    ]);

    $this->view->setVars([
        'latestVersion' => $latest->expose($this->exposers->get('Version')),
        'clientVersion' => $client?->expose($this->exposers->get('Version')),
        'update' => $this->getUpdateType($clientVersion, (string)$latest->getVersion()),
    ]);

    return $this->setRestResponse(true);
}
```

For workflow actions such as `publishAction()`, bind every input, reuse
controller find helpers when permission conditions should apply, and return a
clear count plus model messages when bulk saves partially fail.

For large domain workflows, keep the controller readable by moving action
groups into traits under a resource-specific folder:

```php
final class RecordController extends AbstractController
{
    use RecordAssign;
    use RecordCompare;
    use RecordExport;
    use RecordImport;
    use RecordMetrics;
    use RecordStatus;
    use RecordAdvanced;
}
```

Use traits for cohesive workflows, not for hiding unrelated helper methods.
Each trait should still follow the controller's permission, binding, response,
and validation rules.

## Allowed Order Fields

Controllers accept client ordering by default for backward compatibility. For
new or security-sensitive resources, opt in to an explicit order policy so only
known public sort keys can reach the query builder.

```php
use Phalcon\Support\Collection;

public function initializeOrderFields(): void
{
    $this->setOrderFields(new Collection([
        'createdAt',
        'status' => true,
        'ownerEmail' => 'Owner.email',
    ]));
}
```

Supported policy shapes match the other REST field collections:

- `['createdAt', 'status']` allows public names that map to the same query
  field.
- `['status' => true]` enables a keyed map entry.
- `['ownerEmail' => 'Owner.email']` lets the API expose a stable public key
  while ordering by a joined alias or model-qualified field.
- false, null, and empty-string values are ignored so inherited policies can be
  disabled during merges.

Do not automatically reuse filter fields as order fields. A column can be safe
to filter while still being expensive or misleading to sort. If a resource uses
the same policy intentionally, copy or merge that collection explicitly:

```php
public function initializeOrderFields(): void
{
    $filterFields = $this->getFilterFields();

    $this->setOrderFields($filterFields === null ? null : clone $filterFields);
}
```

When `orderFields` is null, ordering remains unrestricted. When it is a
collection, unlisted request fields throw an HTTP 403 error. Default order
definitions are parsed through the same path, so include default sort fields in
the policy when a controller opts in.

## Custom Order

Override `initializeOrder()` when the incoming order field needs domain-specific
SQL. Preserve the parent initialization, then replace only the supported field.

```php
use App\Models\Resolution;

public function initializeOrder(): void
{
    parent::initializeOrder();

    if ($this->order?->has('number')) {
        $direction = str_contains(strtolower($this->order->get('number')), 'desc')
            ? 'desc'
            : 'asc';

        $this->order->set('number', [
            "REGEXP_SUBSTR([" . Resolution::class . "].[number], '^[a-z,A-Z]+') {$direction}",
            "CAST(REGEXP_SUBSTR([" . Resolution::class . "].[number], '[0-9]+') AS UNSIGNED) {$direction}",
        ]);
    }
}
```

Only expose custom order fields that the database can support with acceptable
performance.

Computed order fields can either become SQL expressions or two-phase sorts.
Use an SQL expression when the database can compute the value per row:

```php
public function initializeOrder(): void
{
    parent::initializeOrder();

    $order = $this->getOrder();
    if (!$order?->has('totalQuestions')) {
        return;
    }

    $direction = str_contains(strtolower((string)$order->get('totalQuestions')), ' desc')
        ? 'desc'
        : 'asc';

    $order->set('totalQuestions', $this->getTotalQuestionsExpression() . ' ' . $direction);
}

private function getTotalQuestionsExpression(): string
{
    return '(SELECT COUNT(*) FROM [' . SurveyQuestion::class . '] '
        . 'WHERE [' . SurveyQuestion::class . '].[surveyId] = [' . $this->getModelName() . '].[id] '
        . 'AND [' . SurveyQuestion::class . '].[deleted] <> 1)';
}
```

Use a two-phase sort when the value comes from domain services, many related
rows, or PHP-only calculations:

1. Remove the custom order from the normal find criteria.
2. Query matching ids without limit, offset, or order.
3. Compute the metric by id.
4. Sort ids in PHP.
5. Apply pagination to ids.
6. Fetch the final page with `findWith()`.
7. Re-sort the final models to match the paged id order.

This is more expensive than database ordering, so reserve it for fields that
cannot be expressed safely in SQL.

## Advanced Condition Blocks

Use `initializeConditions()` for semantic filters that are too complex for
simple field filters. Store blocks under a named condition key and let the
framework compiler merge them later.

```php
public function initializeConditions(): void
{
    parent::initializeConditions();

    $this->prepareAdvancedConditions();
}

public function prepareAdvancedConditions(): void
{
    $conditions = new Collection([]);
    $this->getConditions()->set('advanced', $conditions);

    $advanced = (array)($this->getParam('advanced') ?? []);
    $stage = (string)($advanced['stage'] ?? '');

    if ($stage === '') {
        return;
    }

    $key = $this->generateBindKey('stage_status');

    $conditions->set('stage_status', [
        'conditions' => '(' . $stage . 'Status = :' . $key . ':)',
        'bind' => [
            $key => (string)($advanced['status'] ?? 'pending'),
        ],
        'bindTypes' => [
            $key => Column::BIND_PARAM_STR,
        ],
    ]);
}
```

For relation-heavy advanced filters, prefer `EXISTS` or `NOT EXISTS` over
fan-out joins. This avoids multiplying result rows when multiple related
records match.

```php
$typeKey = $this->generateBindKey('review_type');
$userKey = $this->generateBindKey('review_user_id');

$conditions->set('reviewed_by_user', [
    'conditions' => '
        EXISTS (
            SELECT 1
            FROM ' . RecordUserStatus::class . ' rus
            WHERE rus.recordId = [' . Record::class . '].[id]
              AND rus.projectId = [' . Record::class . '].[projectId]
              AND rus.type = :' . $typeKey . ':
              AND rus.userId = :' . $userKey . ':
        )
    ',
    'bind' => [
        $typeKey => $stage,
        $userKey => (int)$this->identity->getUserId(),
    ],
    'bindTypes' => [
        $typeKey => Column::BIND_PARAM_STR,
        $userKey => Column::BIND_PARAM_INT,
    ],
]);
```

Advanced condition rules:

- Always call `parent::initializeConditions()` first unless intentionally
  replacing the framework defaults.
- Put advanced filters under one top-level key such as `advanced`.
- Use `generateBindKey()` for every dynamic bind key.
- Keep each block self-contained with `conditions`, `bind`, and `bindTypes`.
- Correlate subqueries to both the primary key and tenant/project key when the
  domain is scoped.
- Preserve intentional switch fall-through with an explicit comment.

## Agent Editing Rules

When an agent edits a REST controller:

1. Inspect the model's generated abstract relationships before using aliases.
2. Inspect existing app controllers for `AbstractController`, exposers,
   transformers, roles, and action names.
3. Keep save, filter, search, expose, with, joins, and permissions separate.
4. Add relation exposure and relation loading together when the response needs
   nested data.
5. Use behavior classes for role/config-driven query changes that should apply
   across actions or controllers.
6. Use binds for dynamic query values.
7. Run targeted API/controller tests when available.
