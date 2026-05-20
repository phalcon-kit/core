# PhalconKit Models, Migrations, And Scaffolding

Use this reference when adding or changing app models, generated model layers,
migrations, scaffold scripts, model aliases, validations, relationships, or
model events in a PhalconKit application.

## Phalcon Baseline

Native Phalcon references:

- Models: https://docs.phalcon.io/5.13/db-models/
- Relationships: https://docs.phalcon.io/5.13/db-models-relationships/
- Model validation: https://docs.phalcon.io/5.13/db-models-validation/
- Migrations: https://docs.phalcon.io/5.13/db-migrations/
- Devtools: https://docs.phalcon.io/5.13/devtools/

PhalconKit scaffolding reads the database and generates app model layers on top
of native Phalcon ORM conventions. Use native docs for ORM vocabulary and
PhalconKit docs for generated ownership, scaffold commands, guessed relations,
and generated app contracts.

## Ownership Boundaries

Generated app model structure usually looks like this:

```text
app/Models/
  AbstractModel.php
  Abstracts/
    VoteAbstract.php
    Interfaces/
      VoteAbstractInterface.php
  Interfaces/
    VoteInterface.php
  Enums/
    VoteStatus.php
  Vote.php
```

Ownership rules:

- `Models/Abstracts/*Abstract.php`: scaffold-generated columns, getters,
  setters, column map, default relationships, and default validations.
- `Models/Abstracts/Interfaces/*AbstractInterface.php`: scaffold-generated
  interface for generated getters/setters and relation methods.
- `Models/Interfaces/*Interface.php`: app-facing model interface. It usually
  extends the generated abstract interface and can hold app-specific contract
  additions.
- `Models/*.php`: app-owned concrete behavior: custom relationships, custom
  validation, event hooks, normalization setters, and domain helpers.
- `Models/AbstractModel.php`: app base model. Use it for shared app-level model
  services or helpers when the app needs them.

Do not hand-edit generated abstract files when the app uses scaffold
regeneration. Put custom behavior in the concrete model or app base model.

## Database-First Model Flow

PhalconKit model work is normally database-first. The database schema is the
source that the scaffold task reads, and the concrete model is where app logic
lives.

Typical flow:

1. Create or adjust tables, columns, indexes, foreign-key-like columns, and DB
   enum columns.
2. Generate, review, and run migrations, or apply the schema change in a local
   development database.
3. Run the scaffold task so generated abstracts, interfaces, enum classes,
   relationships, validations, and model tests match the real database.
4. Keep generated files as a schema mirror.
5. Add business rules, normalization, custom relationships, event hooks, and
   helper methods in concrete models or `Models/AbstractModel`.
6. Align REST save fields, exposers or transformers, permissions, and eager
   loading with the generated model shape.

This lets the app team keep the database model explicit while avoiding repeated
hand-written boilerplate. When the schema changes, regenerate the scaffolded
layer and preserve concrete models unless the overwrite is intentional.

## Migration Helper Scripts

Apps often wrap Phalcon DevTools migrations in small `bin/` scripts so every
developer uses the same config, directory, and deprecation flags.

Generate:

```bash
#!/bin/bash
php -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" ./vendor/bin/phalcon migration generate --config=./devtools.php --directory=./ --migrations=./resources/migrations/ --no-auto-increment --force --verbose --log-in-db "$@"
```

List:

```bash
#!/bin/bash
php -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" ./vendor/bin/phalcon migration list --config=./devtools.php --directory=./ --migrations=./resources/migrations/ --log-in-db "$@"
```

Rollback:

```bash
#!/bin/bash
php -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" ./vendor/bin/phalcon migration rollback --config=./devtools.php --directory=./ --migrations=./resources/migrations/ --no-auto-increment --force --verbose --log-in-db "$@"
```

Run:

```bash
#!/bin/bash
php -d "error_reporting=E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED" ./vendor/bin/phalcon migration run --config=./devtools.php --directory=./ --migrations=./resources/migrations/ --no-auto-increment --force --verbose --log-in-db "$@"
```

Guidelines:

- Keep the migration path consistent, usually `resources/migrations/`.
- Keep DevTools config in one file, usually `devtools.php`.
- Pass `"$@"` so developers can add a table or version argument without editing
  the script.
- Review generated migrations before running them against shared databases.
- Run scaffold regeneration after schema changes when model columns,
  relationships, or enum definitions changed.

## Scaffold Commands

The scaffold task reads the database schema and writes model layers, interfaces,
enums, and tests according to the selected options.

Generate missing app model files only:

```bash
#!/bin/bash
./vendor/bin/phalcon-kit cli scaffold run --src-dir=app/ --namespace=App --models-extend=\\App\\Models\\AbstractModel
```

Regenerate all model layers, including concrete models:

```bash
#!/bin/bash
./vendor/bin/phalcon-kit cli scaffold run --src-dir=app/ --namespace=App --models-extend=\\App\\Models\\AbstractModel --force
```

Regenerate generated layers without overwriting existing concrete models:

```bash
#!/bin/bash
./vendor/bin/phalcon-kit cli scaffold run --src-dir=app/ --namespace=App --models-extend=\\App\\Models\\AbstractModel --force --no-models
```

Run an app CLI task:

```bash
#!/bin/bash
./phalcon-kit cli cron run
```

Scaffold mode rules:

- Use the missing-files command when adding tables and preserving existing app
  customizations.
- Use `--force --no-models` after schema changes when generated abstracts,
  interfaces, enums, and tests need refresh but concrete models contain custom
  code.
- Use full `--force` only when the app intentionally wants scaffold output to
  overwrite concrete model files.
- Keep `--models-extend=\\App\\Models\\AbstractModel` so generated concrete
  models inherit the app base model.
- Use `--table=<table>` or `--exclude=<table>` when a broad regeneration would
  touch unrelated resources.

## What The Scaffold Reads And Writes

The scaffold task reads the active database connection. For each included
table, it describes:

- Table names, filtered by `--table` and `--exclude`.
- Columns, including names, types, sizes, scale, nullability, unsigned flags,
  autoincrement flags, defaults, and enum domains.
- Indexes, especially primary and unique indexes.
- Reference metadata where the adapter exposes it.

It then writes the model layer under the selected `--src-dir` and namespace:

- `Models/Abstracts/*Abstract.php` with properties, comments, getters, setters,
  `addDefaultRelationships()`, `addDefaultValidations()`, and `columnMap()`.
- `Models/Abstracts/Interfaces/*AbstractInterface.php` with generated
  getters, setters, and relationship docblocks.
- `Models/*.php` concrete model shells when not disabled with `--no-models`.
- `Models/Interfaces/*Interface.php` app-facing model interfaces.
- `Models/Enums/*<Column>.php` backed enum classes for DB enum columns.
- Model tests that check concrete/abstract/interface types, getter/setter
  defaults, and column maps.

The CLI still exposes controller-related options, but the current scaffold
`runAction()` has controller generation commented out. Do not promise generated
REST controllers unless the app or core task explicitly enables that path.

## Scaffold Guessing Rules

The scaffold tries to infer every safe convention from the database:

- Table and property names are camelized from database names. For example,
  `user_role.role_id` becomes `UserRole::$roleId`.
- Column maps preserve DB column names while letting app code use camelCase
  property names.
- Generated comments include DB column attributes so agents and developers can
  see type, size, nullability, unsigned, primary, and autoincrement metadata.
- Properties default to `mixed` typing unless typing options are changed.
  `--granular-typings` uses detected PHP scalar types and nullability;
  `--add-raw-value-type` allows `RawValue`; `--protected-properties` changes
  generated property visibility.
- Columns ending in `_id` are guessed as `belongsTo` relations with an
  `<Name>Entity` alias, such as `user_id` to `UserEntity`.
- `parent_id`, `child_id`, `left_id`, and `right_id` can become self-references
  when the target table can be inferred.
- Columns ending in `_by` or `_as` are treated as user references, producing
  aliases such as `CreatedByEntity` and `CreatedAsEntity`.
- Tables with columns like `<current_table>_<current_column>` become
  one-to-many relations with `<OtherModel>List` aliases.
- Join/node tables that connect the current table to another table become
  `hasManyToMany()` relations. Normal node table names produce target aliases
  such as `RoleList`; ambiguous node tables use a fuller path alias to avoid
  collisions.
- Unique and primary indexes become uniqueness validations.
- Numeric, unsigned, string, date, datetime, JSON, boolean, enum, and length
  rules become generated validation helper calls when possible.
- DB enum columns generate PHP enum classes and inclusion validations.
- Raw SQL defaults such as `CURRENT_TIMESTAMP` are not treated as scalar PHP
  defaults.

These guesses are intentionally conservative. If the database naming is too
custom for the scaffolder to infer, add or override the relationship in the
concrete model after `addDefaultRelationships()`.

## Generated Model Feature Layer

Concrete app models extend the generated abstract, and the generated abstract
usually extends the app base model, which usually extends PhalconKit's model
base. The PhalconKit model base provides a broad feature layer through traits:

- Relationship-aware assignment and save behavior.
- Eager loading with `findWith()`, `findFirstWith()`, dynamic
  `findWithBy...()` helpers, and instance `load()`.
- Generic validation helpers for ids, dates, datetimes, JSON, enum domains,
  strings, booleans, unsigned numbers, uniqueness, soft delete, position,
  created/updated/deleted/restored fields, and more.
- Expose helpers, identity helpers, JSON helpers, hashing, security, cache,
  snapshots, replication, slug, UUID, soft delete, blameable fields, lifecycle
  hooks, and model options.
- Stable ORM setup defaults such as dynamic update, cast-on-hydrate,
  integer last insert ids, virtual foreign keys, snapshots on save, and
  disabled automatic not-null validations so app validations stay explicit.

## Concrete Model Baseline

Concrete models should usually extend their generated abstract, implement the
app interface, and call the generated default relationship and validation
helpers.

```php
namespace App\Models;

use App\Models\Abstracts\GroupAbstract;
use App\Models\Interfaces\GroupInterface;

class Group extends GroupAbstract implements GroupInterface, \PhalconKit\Models\Interfaces\GroupInterface
{
    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();
    }

    public function validation(): bool
    {
        $validator = $this->genericValidation();
        $this->addDefaultValidations($validator);

        return $this->validate($validator);
    }
}
```

Implement the matching core interface when the app model replaces a core model
through config `models`. App-only models do not need a core interface.

## App Relationships And Normalizers

Add custom relationships in the concrete model after
`addDefaultRelationships()`. Add docblocks for relation properties and getter
methods when static analysis or agents need to discover them.

```php
namespace App\Models;

use App\Models\Abstracts\UserAbstract;
use App\Models\Interfaces\UserInterface;

/**
 * @property Participant $participantentity
 * @property Participant $ParticipantEntity
 * @method Participant getParticipantEntity(?array $params = null)
 */
class User extends UserAbstract implements UserInterface, \PhalconKit\Models\Interfaces\UserInterface
{
    public function initialize(): void
    {
        parent::initialize();
        $this->addDefaultRelationships();

        $this->hasOne('id', Participant::class, 'userId', [
            'alias' => 'ParticipantEntity',
        ]);
    }

    public function validation(): bool
    {
        $validator = $this->genericValidation();
        $this->addDefaultValidations($validator);

        return $this->validate($validator);
    }

    public function afterValidation(): void
    {
        if ($this->hasChanged('password') && !empty($this->getPassword())) {
            $this->setPassword($this->hash($this->getPassword()));
        }
    }

    public function setFirstName(mixed $firstName): void
    {
        $this->firstName = mb_trim((string)$firstName);
    }

    public function setLastName(mixed $lastName): void
    {
        $this->lastName = mb_trim((string)$lastName);
    }

    public function setEmail(mixed $email): void
    {
        $this->email = mb_strtolower(mb_trim((string)$email));
    }
}
```

Guidelines:

- Call `parent::initialize()` first.
- Call `addDefaultRelationships()` before adding app-only relationships.
- Put normalization in setters when every assignment should be normalized.
- Hash passwords in a validation/update hook, not in controllers.
- Use the model `hash()` helper so security config controls the hash settings.

## Relationship Assignment And Save Behavior

PhalconKit models extend Phalcon relationship persistence instead of leaving
nested saves to every controller. `assign()` first calls `assignRelated()`, then
passes scalar fields to the parent model assignment.

Important rules:

- Relationship payload keys must match a defined relationship alias. Aliases are
  usually available in both lower-case form (`rolelist`) and generated alias
  form (`RoleList`) through Phalcon relationship access.
- Controller save fields act as the write whitelist. If a relation alias is not
  whitelisted, the nested relation payload is skipped.
- A single relation can be assigned with a model instance, a scalar id, or an
  array of fields.
- A many relation can be assigned with model instances, scalar ids, or arrays of
  fields.
- `true` as the first many-relation item means keep missing existing records.
  This is the default and is useful when appending.
- `false` as the first many-relation item means remove missing existing records.
  For soft-delete models, that removal follows the model's delete behavior.
- `belongsTo` records are saved before the parent, then referenced values are
  written back to the parent foreign-key fields.
- `hasOne` and `hasMany` records are saved after the parent, with parent key
  values written into the child records.
- `hasManyToMany` relations create, reuse, restore, or remove intermediate node
  records as needed.
- Nested validation messages are copied back with relationship context and item
  index, which makes relation save errors usable in REST responses.
- Changed relationship aliases are tracked in `dirtyRelated` by lower-case
  alias. Pass the lower-case alias to `hasDirtyRelatedAlias()` or inspect
  `dirtyRelated` in model events when a domain event depends on nested relation
  changes.

One-to-many example using the generated node model relation:

```php
$user->assign([
    'email' => 'test@example.tld',
    'userrolelist' => [
        false, // replace the current list: remove missing UserRole rows
        ['roleId' => 1],
        ['roleId' => 2],
    ],
]);

$user->save();
```

Append or reactivate without removing other children:

```php
$user->assign([
    'userrolelist' => [
        true, // keep missing rows
        ['id' => 2, 'deleted' => 0],
        ['roleId' => 5, 'deleted' => 1],
    ],
]);
```

Many-to-many example using the generated target relation:

```php
$user->assign([
    'rolelist' => [
        false, // replace active role links
        1, // existing Role id
        ['id' => 2],
        ['id' => 3, 'key' => 'changed'],
        ['key' => 'new-role', 'label' => 'New role'],
        new Role(['key' => 'custom'])->assign(['label' => 'Custom']),
    ],
]);
```

Use direct node relations such as `UserRoleList` when the app needs node fields
like `deleted`, `position`, `createdBy`, or relation-specific metadata. Use
target relations such as `RoleList` when the app wants to manage the link and
the target entity together.

## Eager Loading And Relationship Querying

For a deeper implementation guide, read `references/eager-loading.md`.

Use eager loading for API responses, WebSocket snapshots, exports, and domain
helpers that read relation graphs. It avoids repeated lazy queries and keeps
relationship filters close to the read path.

```php
use Phalcon\Db\Column;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

$vote = Vote::findFirstWith([
    'VoteSubmissionList.VoteAnswerList',
    'VoteAllowedParticipantList' => function (QueryBuilder $query) {
        $query->where('deleted = 0');
    },
    'VoteAllowedParticipantList.ParticipantEntity.UserEntity',
], [
    'id = :id:',
    'bind' => ['id' => $id],
    'bindTypes' => ['id' => Column::BIND_PARAM_INT],
]);
```

Common APIs:

- `Model::findWith($with, $parameters)` returns a loaded list.
- `Model::findFirstWith($with, $parameters)` returns one loaded model or null.
- `$model->load($with)` eager-loads relations on an existing model.
- Dotted paths load nested relations such as
  `EventParticipantList.ParticipantEntity.UserEntity.RoleList`.
- Closures receive a PhalconKit eager-loading `QueryBuilder` and can add
  relation-specific `where()` and `orderBy()` clauses.
- Dynamic helpers such as `findFirstWithById()` and `findWithByStatus()` call
  the native `findFirstBy...` or `findBy...` method, then eager-load the
  requested relations.

Keep full model hydration when using eager loading. If custom `columns` are
needed, include `*` as the first column so relation keys remain available.

## Domain Validation Pattern

Use generated validation first, then append domain rules. Return false when
custom validation messages were appended.

```php
use App\Models\Enums\VoteStatus;
use PhalconKit\Filter\Validation;

public function validation(): bool
{
    $validator = $this->genericValidation();
    $this->addDefaultValidations($validator);
    $this->addVoteStatusValidation($validator);

    return !count($this->getMessages()) && $this->validate($validator);
}

public function addVoteStatusValidation(Validation $validator): void
{
    if ($this->getStatus() === VoteStatus::CURRENT->value) {
        $this->addPresenceValidation($validator, ['startedAt', 'timer'], false);
    }
}
```

For uniqueness checks, use model validation helpers:

```php
$this->addUniquenessValidation($validator, [
    'voteId',
    'participantId',
    'iteration',
], false);
```

For authorization-like model rules, append typed messages with an HTTP code:

```php
use Phalcon\Messages\Message;

$this->appendMessage(new Message(
    'not-allowed',
    ['voteId', 'participantId'],
    'Forbidden',
    403,
));
```

Keep complex validation split into small methods such as
`validateVoteStatus()`, `validateVoteUnion()`, and
`validateVoteAllowedParticipant()`. This makes controllers thin and keeps the
save path consistent for REST, CLI, and WebSocket-triggered writes.

## Validating Related Models

When validation depends on relations, retrieve the relation through generated
relationship methods and bind dynamic query values.

```php
use Phalcon\Db\Column;
use Phalcon\Messages\Message;

public function validateVoteAllowedParticipant(Vote $vote, Participant $participant): void
{
    if (empty($vote->getParticipantOnly())) {
        return;
    }

    $allowed = $vote->getVoteAllowedParticipantList([
        'participantId = :participantId: and deleted <> 1',
        'bind' => ['participantId' => (int)$participant->getId()],
        'bindTypes' => ['participantId' => Column::BIND_PARAM_INT],
    ]);

    if (!empty($allowed)) {
        return;
    }

    $this->appendMessage(new Message(
        'not-allowed',
        ['voteId', 'participantId'],
        'Forbidden',
        403,
    ));
}
```

Use identity service helpers from models when the rule depends on the current
identity:

```php
if ($this->getIdentityService()->hasRole(['dev', 'admin'])) {
    // privileged validation path
}
```

Keep the policy obvious: comments, method names, and conditions should agree.

## Model Events And WebSocket Publishing

Use model events to publish small invalidation or snapshot-trigger messages
after a successful update. Do not build the full WebSocket payload inside the
model; publish the channel type and id, then let the WebSocket task query and
expose the current model state.

```php
use App\Models\Enums\VoteStatus;

class Vote extends VoteAbstract implements VoteInterface
{
    public const string CHANNEL = 'vote';

    public bool $publishWebsocket = false;

    public function afterValidationOnUpdate(): void
    {
        $this->publishWebsocket = $this->hasChanged([
            'status',
            'timer',
            'revealed',
            'iteration',
            'startedAt',
        ]) || !empty($this->dirtyRelated['voteparticipantlist']);
    }

    public function afterUpdate(): void
    {
        if (!$this->publishWebsocket) {
            return;
        }

        $this->getDI()->get('redis')->publish('websocket', json_encode([
            'type' => self::CHANNEL,
            'channel' => self::CHANNEL . ':' . $this->getId(),
            'id' => $this->getId(),
        ]));
    }
}
```

Event publishing rules:

- Compute whether to publish before saving finishes, usually in
  `afterValidationOnUpdate()`.
- Publish only after successful persistence, usually in `afterUpdate()`.
- Publish a minimal message: type, channel, id.
- Let the WebSocket task build the client-facing snapshot.
- If the app base model exposes a Redis helper, use the app helper
  consistently; otherwise use the `redis` DI service.

## Domain Helpers

Domain helpers belong in the concrete model when they describe the model's
state and are reused by REST controllers, WebSocket broadcasts, or CLI tasks.

```php
public function getExpireAt(): int
{
    if (empty($this->getStartedAt()) || empty($this->getTimer())) {
        return time();
    }

    $startedAt = strtotime($this->getStartedAt());
    $timerSeconds = strtotime($this->getTimer()) - strtotime('TODAY');

    return $startedAt + $timerSeconds;
}

public function isExpired(): bool
{
    if (empty($this->getStartedAt()) || empty($this->getTimer())) {
        return true;
    }

    return $this->getExpireAt() <= time();
}
```

For aggregate helpers, prefer eager-loaded or batched queries with bind values:

```php
use Phalcon\Db\Column;
use PhalconKit\Mvc\Model\EagerLoading\QueryBuilder;

public function getResultList(): array
{
    $submissions = VoteSubmission::findWith([
        'VoteAnswerList' => function (QueryBuilder $query) {
            $query->where('[' . VoteAnswer::class . '].[deleted] = 0');
        },
    ], [
        'voteId = :voteId: and iteration = :iteration: and deleted = 0',
        'bind' => [
            'voteId' => $this->getId(),
            'iteration' => $this->getIteration(),
        ],
        'bindTypes' => [
            'voteId' => Column::BIND_PARAM_INT,
            'iteration' => Column::BIND_PARAM_INT,
        ],
    ]);

    $result = [];
    foreach ($submissions as $submission) {
        foreach ($submission->voteanswerlist as $answer) {
            $id = $answer->getVoteParticipantId();
            $result[$id] ??= 0;
            $result[$id]++;
        }
    }

    return $result;
}
```

## Model Alias Config

When an app replaces a core model, add a model alias in root config:

```php
'models' => [
    \PhalconKit\Models\User::class => \App\Models\User::class,
    \PhalconKit\Models\Role::class => \App\Models\Role::class,
    \PhalconKit\Models\Group::class => \App\Models\Group::class,
],
```

The concrete app model should implement the matching core interface when core
services depend on that contract. This is important for identity, ACL,
permissions, and model lookup helpers.

## Agent Checklist

When changing models or schema:

1. Start from the database schema and migrations. Confirm the table names,
   column names, indexes, enum domains, and relationship columns are the source
   the scaffold should mirror.
2. Inspect the concrete model, generated abstract, generated abstract
   interface, concrete interface, migration, and config model aliases.
3. Put custom behavior in concrete models or `AbstractModel`, not generated
   abstracts.
4. After schema changes, run migrations and then regenerate scaffolded layers
   with the safest mode for the app.
5. Preserve concrete model customizations by using `--force --no-models` unless
   overwriting concrete models is intentional.
6. Keep controller save fields aligned with model validation rules and
   relationship assignment aliases.
7. Use eager loading, exposers, or transformers for response graphs instead of
   relying on accidental lazy loading.
8. Test model validation and at least one REST save path for changed domain
   rules or nested relationship payloads.
