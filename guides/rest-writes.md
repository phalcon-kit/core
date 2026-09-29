# Writes, Validation, And Batches

These examples extend the [Project resource](first-rest-resource.md). Configure
authentication and the corresponding controller **and model** permissions first.
Set `API_TOKEN` locally to a valid access token. Examples show `view` fragments
unless a complete envelope is labelled explicitly.

## Choose Create, Update, Or Save

| Action | No identity | Matching identity | Unknown/hidden identity |
| --- | --- | --- | --- |
| `create` | Creates; single success 201 | Rejects with 400 | Rejects with 400 |
| `update` | Rejects with 400 | Updates; 200 | Rejects with 404 |
| `save` | Creates; 200 | Updates; 200 | Can create a new row; 200 |

The normal identity is `id`; save intent also recognizes `uuid`. Lookup uses the
model's identity-column configuration. A non-primary UUID column is not
automatically a lookup key merely because the input is named `uuid`.

Choose `/update` when “this record must already exist” matters. `/save` is not a
safe substitute for that requirement: an ID hidden by permission conditions can
fail lookup and lead to creation. Save strips `id`/`uuid` from assigned data;
it does not promise to preserve a supplied identifier on creation.

## Create One Record

```shell
curl http://127.0.0.1:8080/api/project/create \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"label":"Playground","status":"draft","budget":500}'
```

HTTP 201, `response: true`:

```json
{
  "saved": true,
  "mode": "create",
  "data": {"id": 4, "label": "Playground", "status": "draft", "budget": 500},
  "messages": []
}
```

Only fields allowed by `initializeSaveFields()` are assigned. Rejecting unknown
scalar input is an application validation decision; an allowlist does not
necessarily return an error for every discarded key.

## Update A Record

```shell
curl -X PATCH http://127.0.0.1:8080/api/project/update \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":4,"status":"active"}'
```

HTTP 200, `response: true`, `view.mode: "update"`; `view.data` contains the
updated exposed record. Omitted writable attributes retain their existing values.
The generic PUT path also assigns supplied fields; it does not implement a
full-resource replacement contract automatically.

Missing ID, HTTP 400:

```json
{
  "saved": false,
  "messages": [
    {
      "field": "id",
      "message": "Missing identity fields for update.",
      "type": "InvalidUpdate",
      "code": 400,
      "metaData": {"fields": ["id"]}
    }
  ]
}
```

## Validate Business Rules

Generated models validate schema constraints such as uniqueness and value
lengths. Add business validation to the concrete model; do not edit its generated
abstract. For example, a concrete model can append a message and return false
from a cancellable validation/save hook:

```php
/** Prevent activation before the project has a budget. */
public function beforeSave(): bool
{
    if ($this->getStatus() === 'active' && (int)$this->getBudget() <= 0) {
        $this->appendMessage(new \Phalcon\Messages\Message(
            'An active project needs a positive budget.',
            'budget',
            'BudgetRequired'
        ));
        return false;
    }
    return true;
}
```

Compose this with existing hooks if your application already implements them.
Ordinary validation messages produce HTTP 422; a message carrying a 400–499
code can select that HTTP status. A failed persistence operation without messages
falls back to 400. Failures do not include a saved `data` object or `mode` by
default. Database exceptions are not guaranteed to become validation messages.

## Save A Batch

A top-level JSON list invokes batch processing:

```shell
curl http://127.0.0.1:8080/api/project/create \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '[{"label":"New park","budget":600},{"label":"Community garden"}]'
```

On the tutorial database, the second label already exists. HTTP 207 has
`response: false` and this `view` (generated ID varies):

```json
{
  "saved": false,
  "messages": [{"type": "summary", "message": "1 of 2 entities were not saved."}],
  "results": [
    {
      "saved": true,
      "mode": "create",
      "data": {"id": 5, "label": "New park", "status": "draft", "budget": 600},
      "messages": []
    },
    {
      "saved": false,
      "messages": [{
        "field": "label",
        "message": "not-unique",
        "type": "Phalcon\\Filter\\Validation\\Validator\\Uniqueness",
        "code": 0,
        "metaData": []
      }]
    }
  ],
  "stats": {"total": 2, "saved": 1, "failed": 1}
}
```

Results correspond to input positions. Inspect each row, not only root messages.
The root list contains a summary rather than all validation details.

| Batch outcome | HTTP | `response` | What persisted |
| --- | --- | --- | --- |
| All saved | 200 | true | Every successful row |
| Some saved | 207 | false | Successful rows remain saved |
| All rejected | 422 | false | No row reported saved |
| Empty list | 200 | true | Nothing; all stats are zero |

An empty JSON object also decodes into PHP's empty array and reaches the empty
batch path. Validate nonempty payloads if your endpoint requires a write.
Non-object batch items receive an `InvalidPayloadRow` failure. Runtime exceptions
may still interrupt processing; the batch helper is not a transactional job
processor.

There is no batch-wide transaction or automatic idempotency key. For an
all-or-nothing operation, implement a service that owns the transaction and
fails it when any row is rejected. Retry only failed rows after a partial result;
replaying successful creates can create duplicates without a unique key.

## Delete

```shell
curl -X DELETE 'http://127.0.0.1:8080/api/project/delete?id=4' \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN"
```

HTTP 200, `response: true`:

```json
{
  "deleted": true,
  "data": {"id": 4, "label": "Playground", "status": "active", "budget": 500},
  "messages": []
}
```

Deletion delegates to the model. A model with Core's configured soft-delete
behavior marks the row deleted; otherwise the model/database controls physical
deletion. A subsequent normal `find-first` no longer sees a soft-deleted row.
A missing or inaccessible target returns 404.

## Restore

Restoration needs a model implementing `SoftDeleteInterface`, a `restore`
controller grant, the relevant model grants, and a query that can see deleted
rows. The normal `deleted = 0` condition otherwise prevents lookup.

A focused controller override can remove only that condition for restoration:

```php
public function getSoftDeleteColumn(): ?string
{
    return $this->dispatcher->getActionName() === 'restore'
        ? null
        : parent::getSoftDeleteColumn();
}
```

Keep ownership/tenant conditions in place. Add POST to your method policy:

```shell
curl http://127.0.0.1:8080/api/project/restore \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":4}'
```

Success is HTTP 200 with `view.restored: true`, `view.data`, and
`view.messages: []`. Missing targets return 404; validation failures return
400/422 or an explicit model message status. Unsupported model interfaces are
configuration errors, not client validation errors.

## Reorder

For a model configured with Core's position behavior and `PositionInterface`,
grant `reorder`, constrain its row scope, and send:

```shell
curl http://127.0.0.1:8080/api/task/reorder \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":12,"position":2}'
```

Success is HTTP 200 with `view.reordered: true`, exposed `data`, and `messages`.
The tutorial's Project table has no position column; this is a separate resource
capability. Define position grouping in the model so reordering one project's
tasks cannot move another project's tasks. Test both the target and neighboring
positions after the operation.

For nested child writes, continue with [Relationships](rest-relationships.md).
