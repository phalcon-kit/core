# Counts, Aggregates, Facets, And Exports

Examples use the three original [Project rows](first-rest-resource.md): two
active projects and one draft, with budgets 1200, 3000, and 800. Reset that fixture
before comparing exact totals after running write examples.

## Count A Filtered List

Grant the Project model `count` as well as `find`, and grant the controller's
`count` action when using its endpoint. Model aggregate grants are separate
from controller action grants. A cancelled model count returns zero, so a zero
result alone does not prove the table is empty.

```shell
curl --get http://127.0.0.1:8080/api/project/find \
  --data-urlencode 'limit=2' \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'count=1'
```

`view.data` contains two records, and `view.count` is 3. Pagination is removed
for the count query. Filters, row permissions, and soft-delete conditions remain.

List totals are opt-in. The supported selectors are `count`, `groupedCount`,
`bucketTotal`, and `totalCount`. Request multiple names with
`count=count,totalCount`, a list, or an enabled map.

```php
public function initializeFindActionCountFields(): void
{
    $this->setFindActionCountFields(['count', 'totalCount']);
}
```

A null list-count policy permits the supported names; `[]` denies count
requests. Requesting an unsupported or denied name returns 403. Counts can add
queries: omit them on endpoints that do not need pagination totals.

## Count Without Fetching Data

```shell
curl http://127.0.0.1:8080/api/project/count
```

HTTP 200, `view`:

```json
{"count": 3}
```

To group by status:

```shell
curl 'http://127.0.0.1:8080/api/project/count?group=status'
```

`view.count` becomes a list of grouped rows; order is not guaranteed:

```json
{"count": [{"status":"draft","rowcount":1},{"status":"active","rowcount":2}]}
```

Configure optional count-action fields on the controller:

```php
public function initializeCountActionResponseFields(): void
{
    $this->setCountActionResponseFields([
        'groupedCount', 'bucketTotal', 'totalCount',
    ]);
}
```

| Field | Meaning |
| --- | --- |
| `count` | Normal count result: scalar or grouped rows |
| `groupedCount` | The raw grouped result repeated under an explicit name |
| `bucketTotal` | Sum of the returned group buckets |
| `totalCount` | Separate count with grouping removed |

For the example, `bucketTotal` and `totalCount` both equal 3. On joined queries,
a root record may belong to multiple buckets; summing buckets is not always the
number of unique projects. Joined counts infer a distinct root identity for a
single-column primary key when no explicit count column is configured. Composite
keys and custom aggregate columns require an application-specific count policy.

Grouping accepts identifiers, not client SQL expressions. It is not restricted
by the expose list. If grouping is public, constrain the allowed group names in
your controller; output visibility is not query authorization.

## Distinct Values For A Filter Menu

The tutorial permits the `status` field:

```php
public function initializeDistinctActionFields(): void
{
    $this->setDistinctActionFields(['status']);
}
```

```shell
curl 'http://127.0.0.1:8080/api/project/distinct?field=status'
```

HTTP 200, `view`:

```json
{"data":["draft","active"],"field":"status","count":2}
```

The action orders by the selected database field ascending. ENUM columns can
follow their declaration order; do not assume alphabetical ordering.

Distinct uses filters, permissions, joins, limit, and offset. Its `count` is the
number of values **on this page**, not the total number of possible values. Null
values can appear when the field/database allows them. A missing/disallowed field
returns 400; null/empty distinct policy enables no fields. Controller-owned alias
maps can give clients stable field names without accepting arbitrary expressions.

## Sum, Average, Minimum, Maximum

Select the aggregate column on the server before the query is initialized:

```php
public function initialize()
{
    $action = $this->dispatcher->getActionName();
    if (in_array($action, ['sum', 'average', 'minimum', 'maximum'], true)) {
        $this->setColumn(['budget']);
    }
    parent::initialize();
}
```

Combine this with your existing method guard instead of declaring two
`initialize()` methods. Grant `sum`, `average`, `minimum`, and `maximum` and add
them to the read method map. Also add `sum` and `average` to the Project model
permission list (alongside `find` and `count`):

```php
\App\Models\Project::class => ['find', 'count', 'sum', 'average'],
```

The default model security behavior checks `sum` and `average` independently.
Their cancelled operations return zero; missing grants can look like empty data.

```shell
curl http://127.0.0.1:8080/api/project/sum
curl http://127.0.0.1:8080/api/project/average
curl http://127.0.0.1:8080/api/project/minimum
curl http://127.0.0.1:8080/api/project/maximum
```

Expected view values for the original fixture:

| Action | Value |
| --- | --- |
| `sum` | `sum: 5000` |
| `average` | `average: approximately 1666.6667` (database precision varies) |
| `minimum` | `minimum: 800` |
| `maximum` | `maximum: 3000` |

Filters and row conditions still apply; limit/offset do not. Grouped calculations
return result rows instead of one scalar. Minimum/maximum preserve database
values, including strings/datetimes and null for no value. Numeric values may be
integers, floats, or decimal strings depending on the operation and driver.
Minimum/maximum can return false when cancelled; count/sum/average use zero. Avoid blindly coercing every
aggregate into a float, especially for exact monetary values.

A request `column=...` is not the column-selection contract. Define separate
server-owned metrics or validate a small metric selector in a custom action.

## Export A Page

Grant `export` and allow GET in the method map. JSON works without an extra
export library:

```shell
curl --get http://127.0.0.1:8080/api/project/export \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  --data-urlencode 'contentType=json' \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'limit=100' \
  --output projects.json
```

The body is a JSON array of exposed rows, **without** the REST envelope. Export
uses the normal query and expose policy, including pagination. It does not
silently export all matching records or load relationships requested with `with`.
Use an application export job for complete, large, or snapshot-consistent exports.

## CSV, XML, And Excel

Install the library for the format you expose:

| `contentType` | Composer package | Response type |
| --- | --- | --- |
| `json` | None beyond Core | `application/json` |
| `csv` | `league/csv` | `text/csv` |
| `xml` | `spatie/array-to-xml` | `application/xml` |
| `xlsx` | `shuchkin/simplexlsxgen` | Excel workbook |

Use a release satisfying Core's Composer suggestions. MIME forms such as
`text/csv` also work. The action reads `contentType`/`content-type`, then the
request's Content-Type; the `Accept` header alone does not choose the format.
An unspecified/unsupported format returns HTTP 400.

```shell
composer require league/csv
curl --get http://127.0.0.1:8080/api/project/export \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  --data-urlencode 'contentType=csv' \
  --data-urlencode 'order=id asc' \
  --data-urlencode 'limit=100' \
  --output projects.csv
```

CSV columns come from the exposed row keys. For the original fixture, the
logical content is:

```csv
"id","label","status","budget"
"1","Community garden","active","1200"
"2","Library renovation","draft","3000"
"3","River cleanup","active","800"
```

Defaults include a UTF-8 BOM and CRLF line endings. CSV options include string
`delimiter`, `enclosure`, `escape`, `endOfLine`, and `outputBOM`, and boolean
`necessaryEnclosure`, `keepEndOfLines`, and `skipIncludeBOM`. `mode=mac` selects
UTF-16LE/tab-oriented output. Invalid option types return 400. Test output in the
spreadsheet/importer your users actually use, including non-ASCII text and
formula-like values; application export policy owns spreadsheet formula safety.

XML options include `rootElement`, `xmlEncoding`, `xmlVersion`,
`addXmlDeclaration`, and the converter's options. Keep complex converter
configuration server-owned for a stable endpoint. All formats are attachments
with a filename derived from the model and current date.
