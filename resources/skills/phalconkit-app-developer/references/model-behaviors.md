# PhalconKit Model Behaviors

Use this reference when changing concrete models, app base models, generated
abstract models, relationship assignment, model validation, audit behavior,
soft delete, UUID/slug behavior, cache behavior, replication, or model-level
security.

## Phalcon Baseline

Native Phalcon references:

- Models: https://docs.phalcon.io/5.17/db-models/
- Relationships: https://docs.phalcon.io/5.17/db-models-relationships/
- Behaviors: https://docs.phalcon.io/5.17/db-models-behaviors/
- Model validation: https://docs.phalcon.io/5.17/db-models-validation/
- Model events: https://docs.phalcon.io/5.17/db-models-events/

PhalconKit model behavior builds on native `Phalcon\Mvc\Model`, relationships,
events, validation, and behavior hooks. Use native docs for ORM lifecycle and
PhalconKit docs for the added assignment, audit, security, UUID, slug, and
relationship persistence behavior.

## Base Model Stack

`PhalconKit\Mvc\Model` initializes a broad feature stack in `initialize()`:

- Options manager.
- Stable ORM setup defaults.
- Model events manager.
- Dynamic update.
- Cache behavior.
- Snapshot behavior.
- Replication lag handling.
- Soft delete behavior.
- Position behavior.
- Model-level ACL security.
- Blameable/audit behavior.
- Created/updated/deleted/restored fields.
- Slug behavior.
- UUID behavior.

Concrete app models should call `parent::initialize()` before adding generated
and custom relationships:

```php
public function initialize(): void
{
    parent::initialize();
    $this->addDefaultRelationships();
    $this->hasOne('id', Participant::class, 'userId', ['alias' => 'ParticipantEntity']);
}
```

## Options

Model traits read behavior options from the model options manager:

```php
$this->getOptionsManager()->set('uuid', [
    'field' => 'uuid',
]);
```

Prefer model options or an app base model for cross-cutting behavior changes.
Use concrete model overrides for domain-specific behavior.

## Relationship Assignment

PhalconKit overrides model `assign()` to handle related records before normal
attribute assignment.

Supported payload shapes:

```php
// belongsTo or hasOne
[
    'UserEntity' => ['id' => 10],
]

// hasMany append/merge
[
    'VoteParticipantList' => [
        ['participantId' => 1],
        ['participantId' => 2],
    ],
]

// hasMany or hasManyThrough with missing relation cleanup
[
    'VoteParticipantList' => [
        false,
        ['participantId' => 1],
        ['participantId' => 2],
    ],
]

// scalar shortcut for a referenced key
[
    'UserEntity' => 10,
]
```

Rules:

- Only relation aliases that exist on the model are processed.
- Whitelists/save fields control which relation aliases are assignable.
- Existing related records are found by primary key first, then relationship
  fields.
- New related entities are created when no existing record is found.
- `false` in a relation list means missing existing relations should be deleted
  or soft-deleted where applicable.
- `true` in a relation list means keep missing existing relations.

## Save Behavior

The relationship trait handles:

- `belongsTo` records before the master record is saved.
- `hasOne`, `hasMany`, `hasOneThrough`, and `hasManyThrough` records after the
  master record is saved.
- Composite relationship keys.
- Nested relationship context for validation messages.
- Many-to-many intermediate records.
- Restoring previously soft-deleted intermediate records.
- Missing node cleanup when `keepMissingRelated` is false.

When validation fails in a related entity, messages are copied back to the
source model with relation context. Preserve that behavior when customizing
save flows.

## Dirty Related

Models track assigned relation aliases in `dirtyRelated`.

Use this to determine whether a model save includes relation changes:

```php
if ($this->hasDirtyRelatedAlias('voteparticipantlist')) {
    // publish websocket update or recompute derived state
}
```

Aliases are stored in lowercase. Check the app's existing alias casing before
adding custom logic.

## Validation Helpers

The `Validate` trait exposes reusable helpers used by generated abstracts and
concrete models:

- `genericValidation()`
- `addPresenceValidation()`
- `addUnsignedIntValidation()`
- `addUnsignedBigIntValidation()`
- `addNumberValidation()`
- `addStringLengthValidation()`
- `addInclusionValidation()`
- `addUniquenessValidation()`
- `addEmailValidation()`
- `addDateValidation()`
- `addDateTimeValidation()`
- `addJsonValidation()`
- `addColorValidation()`
- `addIdValidation()`
- `addPositionValidation()`
- `addSoftDeleteValidation()`
- `addUuidValidation()`
- `addCreatedValidation()`, `addUpdatedValidation()`,
  `addDeletedValidation()`, `addRestoredValidation()`

Generated abstracts call `addDefaultValidations()`. Concrete models should
start from `genericValidation()`, add defaults, then add domain rules:

```php
public function validation(): bool
{
    $validator = $this->genericValidation();
    $this->addDefaultValidations($validator);
    $this->addUniquenessValidation($validator, ['voteId', 'participantId'], false);
    return $this->validate($validator);
}
```

## Security Behavior

The model security behavior checks ACL permissions before operations such as:

- `find`, `findFirst`, `count`, `sum`, `average`
- `create`, `update`, `delete`, `restore`, `reorder`

If a model class is not registered as an ACL component, the model gets a
not-found message. If the current identity lacks permission, the model gets a
forbidden message.

Rules:

- Keep model operation permissions in config.
- Use permission config features for model actions called by controllers,
  tasks, and WebSocket workers.
- Disable or bypass model security only in narrowly scoped maintenance/import
  code where the app has an explicit trusted role.

## Blameable And Audit

Blameable behavior creates audit rows and optional audit detail rows after
create/update. It uses snapshots and changed fields to reduce update noise.

It resolves audit, audit detail, and user classes through the model map. When
an app maps these core models, make sure the app models implement the matching
core interfaces.

Runtime toggles can disable parent audit rows or detail rows for imports,
migrations, or hot paths. Keep such toggles local to the operation.

Audit is disabled by default. Applications that want audit rows should opt in,
usually through the model `blameable` options:

```php
[
    'blameable' => [
        'auditEnabled' => true,
    ],
]
```

Applications that do not install audit tables can omit this option. If audit is
enabled while the audit tables are absent, PhalconKit treats the missing audit
storage as an optional feature and skips the audit write.

If an application provides a custom Blameable behavior subclass, it can instead
change the protected `$auditEnabled` default. Explicit `auditEnabled` options
always override subclass defaults.

Audit detail rows remain enabled when audit is enabled. Disable detail rows with
`auditDetailEnabled => false`.

## Soft Delete And Restore

Soft delete defaults:

- field: `deleted`
- deleted value: `1`

Use:

```php
$entity->isDeleted();
$entity->restore();
$entity->disableSoftDelete();
$entity->enableSoftDelete();
```

The restore flow fires `beforeRestore`, `notRestored`, and `afterRestore` when
ORM events are enabled.

## Position

The position behavior:

- Defaults empty `position` to the current max plus one.
- Reorders sibling rows after save when the position changes.
- Supports `reorder()`.

```php
$entity->reorder(3);
```

Use app-specific ordering logic in the concrete model or controller when the
sort expression is not a simple position field.

## Replication

Replication behavior activates when
`database.drivers.mysql.readonly.enable` is true. It configures:

- write connection service: usually `db`
- read connection service: usually `dbr`
- lag window in milliseconds

After writes, reads are forced to the write connection until the lag window
expires. After the lag window expires, model reads use the configured read
connection service again. This avoids stale reads immediately after
create/update/delete/restore without disabling replicas for normal reads.

## Snapshot And Change Helpers

Snapshot behavior keeps snapshots by default and provides
`hasChangedCallback()`:

```php
$this->hasChangedCallback(fn () => date('Y-m-d H:i:s'), false);
```

Use snapshots for derived fields, audit checks, and publish-on-change behavior.

## Slug, UUID, JSON, And Hash Helpers

- Slug behavior normalizes the configured slug field before validation.
- UUID behavior fills the configured UUID field on create.
- JSON trait wraps `json_encode()` and `json_decode()` with depth validation.
- Hash trait uses the configured security salt and work factor.

For low-level security details, read `security-and-random.md`.
