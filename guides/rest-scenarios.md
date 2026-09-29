# API Scenario Checklist

Use this matrix as a contract checklist for each application resource. The
linked guides contain request syntax, controller setup, and response examples.
Not every action belongs on every resource; a denied/unpublished capability is
an intentional result when your API does not offer it.

## Reads And Inputs

| Scenario | Request/input | Expected result |
| --- | --- | --- |
| List | `find?order=id asc` | 200, `response: true`, `view.data` list |
| Empty list | Valid filter matching no rows | 200, empty `data` |
| Detail | `find-first?id=1` | 200, one `data` object |
| Missing/hidden detail | Unknown or unauthorized ID | 404; no record data |
| Unsaved defaults | `new` | 200, unsaved exposed model; no insert |
| Body fields | POST/PUT/PATCH JSON object | Body supplies input; query is not merged |
| DELETE identity | `delete?id=1` | Query ID used; JSON body is not the normal source |
| Wrong verb | GET on a guarded write action | 405, unchanged database |
| Empty write | `[]` or decoded empty object | Empty batch succeeds but writes nothing; reject in app if unwanted |

See [Requests And Responses](rest-requests-and-responses.md).

## Query Controls

| Scenario | Input | Expected result |
| --- | --- | --- |
| Equality/range/list | `=`, `between`, `in` filters | Only qualifying IDs |
| Nested OR | AND root with nested array | Correct grouped result, not flattened conditions |
| Search | Multiple terms, several allowed fields | Every term matches at least one configured field |
| Unconfigured search | No search fields | No search condition added |
| NULL/empty | Unary null/empty operators | Match SQL NULL/trim semantics explicitly |
| Negative text | Negative contains/like | Check NULL and no-related-row cases |
| Prefix exclusion | `not like: "prefix%"` | Excludes prefix; do not rely on the current negative-prefix shorthand |
| Numeric contains | JSON integer versus query string | Equality versus text behavior; prefer explicit `=` |
| Invalid field shape | SQL expression as client field | 400 |
| Blocked field/operator | Outside allowlist/unsupported operator | 403 |
| Page one/two | Stable order, limit and offset | Expected IDs without relying on incidental DB order |
| Excessive limit | Above configured maximum | 400 |
| Empty later page | Offset beyond matches | 200, empty data; full total remains available |

See [Filtering](rest-filtering.md).

## Writes

| Scenario | Input | Expected result |
| --- | --- | --- |
| Create | One valid object without identity | 201, saved true, mode create |
| Create with identity | `create` plus id/uuid | 400; no unintended update |
| Update | Existing permitted identity | 200, saved true, mode update |
| Update without identity | Missing ID | 400 InvalidUpdate |
| Update absent/hidden target | Unknown/unauthorized ID | 404; no creation |
| Save without target | `save` with missing/unmatched identity | May create; use update for must-exist semantics |
| Uniqueness/validation | Duplicate label/invalid business state | 422; messages describe failure |
| Protected field | Client submits owner/tenant/admin field | Field not assigned; app may explicitly reject |
| Batch all pass | Valid list | 200, every row saved |
| Batch mixed | Valid and invalid rows | 207, response false, successful rows persist |
| Batch all fail | Invalid list | 422, per-row messages |
| Invalid batch item | Scalar within list | InvalidPayloadRow for that position |
| Delete | Visible target | 200 with deleted; reload checks actual model deletion behavior |
| Delete missing | Unknown target | 404 |
| Restore | Deleted target visible to restore policy | 200 with restored; normal reads see it again |
| Reorder | Position-capable target | 200 with reordered; verify neighbors and grouping |
| Unsupported behavior | Restore/reorder on incompatible model | Configuration error; do not publish action |

See [Writes And Batches](rest-writes.md).

## Relationships

| Scenario | Input | Expected result |
| --- | --- | --- |
| Default graph | `find-with` without selector | Configured graph and constraints |
| Selected graph | Allowed `with` path | Only selected graph, retaining parent constraints |
| No graph | `with=0` | Root data without eager loading |
| Blocked graph | Unknown relation | 403 |
| Invalid selector | `with=true` | 400 |
| Plain read with selector | `find?with=...` | Does not implicitly become find-with |
| Child exposure | Loaded child has private columns | Only explicitly allowed child fields |
| Related positive text | Child contains term | Root with a qualifying child |
| Related negative text | Child does not contain term | No qualifying child with the positive term, including no-child roots |
| Same-child filters | Same alias/polarity AND predicates | One child satisfies the combined condition |
| Independent children | Different bracketed scopes | Separate qualifying children allowed |
| Nested write | Allowed child list | Authorized child updates/creates, correct parent link |
| Cross-parent child ID | Direct ownership enforcement enabled | Reject; original parent link remains intact |
| Omitted child | Keep-missing default | Existing child remains |
| Replacement-style list | Explicit keep-missing false policy | Verify removals; never assume patch semantics |

See [Relationships](rest-relationships.md).

## Counts And Files

| Scenario | Input | Expected result |
| --- | --- | --- |
| List count | `count=1` | Total ignores pagination but keeps row conditions |
| No count requested | Omitted count | No count metadata/query required by the action |
| Count blocked | Disallowed selector | 403 |
| Grouped count | `count?group=status` | Group rows rather than one scalar |
| Bucket versus total | Joined/multi-bucket query | Verify sum versus distinct root total separately |
| Distinct allowed | `distinct?field=status` | Scalar values, selected field, page count |
| Distinct denied/missing | Unknown/no field | 400 |
| Aggregate | Configured column plus action | Correct type/value, no pagination |
| Empty aggregate | No matching values | Database/native null/false/result contract handled |
| Export format | Explicit contentType | Attachment, no JSON envelope |
| Export bounds | Filters/limit/offset/expose list | Only permitted selected rows and fields |
| Export missing library | Optional format without dependency | Deployment/configuration failure; install before exposing format |
| Unknown format | Unsupported contentType | 400 |

See [Counts, Aggregates, And Exports](rest-aggregates.md).

## Identity And Operations

Verify valid/invalid login, cookie continuity, token refresh, logout, deleted
users, role membership, and cross-tenant denials using [Authentication](authentication.md).
Repeat relevant reads, aggregates, exports, and writes as different identities.

Verify the [WebSocket](web-server-and-websocket.md) ping/error protocol and any
application-owned subscription/reconnect behavior independently from HTTP.
Record the actual status, selected IDs, response fields, and persisted state in
[application tests](application-testing.md).
