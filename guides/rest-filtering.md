# Filtering, Search, Sorting, And Pagination

Use the [Project tutorial's controller](first-rest-resource.md), whose queryable
fields are `id`, `label`, `status`, and `budget`. Query filters use **`filters`**,
a list of objects. Each object names `field`, `operator`, and usually `value`.

## Equality

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'filters[0][field]=status' \
  --data-urlencode 'filters[0][operator]==' \
  --data-urlencode 'filters[0][value]=active' \
  --data-urlencode 'order=id asc'
```

For the tutorial's original rows, `view.data` contains projects 1 and 3.
`filter[status]=active` is not the REST filter format.

For JSON-capable query clients, the equivalent input object is:

```json
{"filters":[{"field":"status","operator":"=","value":"active"}],"order":"id asc"}
```

A POST to `/find` can read this body if your resource permits POST for reads.
With the tutorial's GET-only method policy, encode it in the query string instead.
Do not send a JSON body with GET and expect the REST helpers to read it.

## Multiple Conditions And Groups

Top-level filters combine with AND. This input selects active projects with
budgets from 500 through 1500, inclusive:

```json
{
  "filters": [
    {"field": "status", "operator": "=", "value": "active"},
    {"field": "budget", "operator": "between", "value": [500, 1500]}
  ]
}
```

Result: projects 1 and 3. Use `value[0]` and `value[1]` when URL-encoding a range.

Nested arrays form groups. The default logic alternates by depth: root AND,
first nested group OR, next nested group AND. This selects
`status = active AND (budget < 1000 OR label contains garden)`:

```json
{
  "filters": [
    {"field": "status", "operator": "=", "value": "active"},
    [
      {"field": "budget", "operator": "<", "value": 1000},
      {"field": "label", "operator": "contains", "value": "garden"}
    ]
  ]
}
```

Result: projects 1 and 3. For example, encode the first nested field as
`filters[1][0][field]=budget`. Each node may specify `logic: "and"`, `"or"`, or
`"xor"`; it controls that node's connector. The first concrete node's explicit
logic in a group also controls how that group connects to its parent. Prefer
simple nested AND/OR groups and test the selected IDs when composing expressions.
XOR and some operators depend on the database dialect.

## Operator Reference

The examples below are **filter objects**, not whole requests. Wrap them in a
`filters` list. Use these canonical spellings; the parser also accepts aliases.

| Operators | Value | Example and meaning |
| --- | --- | --- |
| `=`, `!=`, `<>`, `>`, `>=`, `<`, `<=` | Scalar | `{"field":"budget","operator":">=","value":1000}` selects 1 and 2 |
| `<=>` | Scalar/null | MySQL-style null-safe equality; verify dialect support |
| `in`, `not in` | Nonempty list | `{"field":"status","operator":"in","value":["draft","active"]}` selects all three |
| `between`, `not between` | Exactly two bounds | `{"field":"budget","operator":"between","value":[800,1200]}` selects 1 and 3 |
| `like`, `not like` | SQL pattern | `{"field":"label","operator":"like","value":"Community%"}` selects 1 |
| `starts with`, `ends with` | Text | `starts with: "Community"` selects 1; `ends with: "cleanup"` selects 3 |
| `contains`, `does not contain` | Text | `contains: "garden"` selects 1; negative selects 2 and 3 |
| `contains word`, `does not contain word` | Text | Word-oriented regular-expression matching; verify collation/dialect behavior |
| `regexp`, `not regexp` | Pattern | Database regular-expression matching; syntax is database-specific |
| `is null`, `is not null` | Omit `value` | Tests SQL NULL |
| `is true`, `is not true`, `is false`, `is not false` | Omit `value` | Database boolean tests |
| `is empty`, `is not empty` | Omit `value` | NULL or trimmed empty string; inverse excludes those values |
| `does not start with`, `does not end with` | Text | Negative text matching; see the prefix caveat below |
| `distance sphere equals`, `distance sphere greater than`, `distance sphere greater than or equal`, `distance sphere less than`, `distance sphere less than or equal` | Five numeric values | Low-level spherical distance expression; see spatial limitations below |

The parser recognizes bare `is`/`is not`, but use the complete unary spellings
above for null/boolean tests. Do not supply a value to a unary operator.

### Text, Numbers, And NULL

- `contains` with a JSON integer becomes equality; an integer list becomes IN.
  Query-string values are strings, so `"12"` has text semantics while JSON `12`
  has numeric semantics. Use `=` or `in` when numeric intent should be explicit.
- LIKE `%` and `_` are pattern wildcards. `contains` and `search` do not promise
  literal wildcard escaping. For literal matching, add an application filter.
- Negative text operators include NULL/empty handling. Ordinary `!=` uses SQL
  NULL rules and does not automatically include NULL rows.
- Case/accent matching follows the database collation, not a universal API rule.

### Current Operator Limitations

The current `does not start with` compiler uses a suffix-shaped pattern. To
exclude a prefix, use `not like` with `value: "Community%"` and decide explicitly
whether NULL should be included. Do not rely on the misleading operator name.

Spatial distance operators take two coordinate pairs and a threshold as a
five-number value. They construct a distance expression from those values;
they are not a general “distance from this row's geometry column” API. A useful
nearby-resource endpoint should define and test its own server-owned spatial
query and database-specific units.

## Search

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'search=community garden'
```

The tutorial searches `label`. Each space-separated term must match at least
one configured search field: terms combine with AND, fields for a term with OR.
Both words match project 1. Search without configured fields adds no condition.
It does not scan every model column automatically.

## Sorting

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'order=budget desc,id asc'
```

Original project IDs: 2, 1, 3. Supported order forms include a comma-separated
string, field/direction arrays, and an enabled map. Use `asc` or `desc`; unknown
direction text normalizes to ASC, so validate a UI sort selector yourself.

Always include a deterministic tie-breaker such as `id` for pagination. Field
names must be permitted by `initializeOrderFields()`. Server-owned mappings may
map a public sort name to a trusted expression; never accept raw client SQL.

## Pagination

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'limit=2' \
  --data-urlencode 'offset=0' \
  --data-urlencode 'count=1'
```

The first page contains IDs 1 and 2, with `view.count: 3`. `offset=2` returns ID 3.
Defaults are limit 10 and offset 0; the default maximum limit is 100. Exceeding
it returns HTTP 400. Inputs use absolute-integer filtering: negative values are
not a supported “unlimited” convention.

Set a resource's limits on the server, for example:

```php
protected ?int $maxLimit = 100;

public function defaultLimit(): ?int
{
    return 20;
}
```

Offset pagination can shift when rows change between requests. For a stable
export or synchronization job, use an application snapshot or an explicitly
implemented cursor strategy.

## Invalid Filters

| Input | Expected result |
| --- | --- |
| Missing field/operator/value for a binary comparison | 400 |
| Malformed field identifier | 400 |
| Field outside the resource filter list | 403 |
| Unsupported operator | 403 |
| Nonempty value supplied to a unary operator | 403 |
| Valid filter with no matches | 200 with `view.data: []` |

These are query policies, not full JSON-schema validation. Reject malformed
ranges, oversized lists, or complex UI-generated trees in your application
before relying on low-level SQL compilation. For related fields and same-child
matching, continue with [Relationships](rest-relationships.md).
