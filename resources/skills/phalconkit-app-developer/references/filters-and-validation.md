# PhalconKit Filters And Validation

Use this reference when handling input filters, sanitizers, validators, REST
filter/search conditions, advanced condition blocks, or model validation.

## Phalcon Baseline

Native Phalcon references:

- Filters and sanitizers: https://docs.phalcon.io/5.13/filter-filter/
- Validation component: https://docs.phalcon.io/5.13/filter-validation/
- Model validation: https://docs.phalcon.io/5.13/db-models-validation/

PhalconKit extends the native filter service and validation component. Check the
native Phalcon docs first when the question is about built-in sanitizers,
request filtering, validator options, validation messages, or model validation
events.

Native Phalcon filters in the current docs:

```text
absint, alnum, alpha, bool, email, float, int, ip, lower, lowerfirst,
regex, remove, replace, special, specialfull, string, stringlegacy,
striptags, trim, upper, upperfirst, upperwords, url
```

Native Phalcon validation classes in the current docs:

```text
Alnum, Alpha, Between, Callback, Confirmation, CreditCard, Date, Digit,
Email, ExclusionIn, File, Identical, InclusionIn, Ip, Numericality,
PresenceOf, Regex, StringLength, Uniqueness, Url
```

## Filter Service

The `filter` DI service is provided by `PhalconKit\Provider\Filter` and returns
`PhalconKit\Filter\Filter`.

PhalconKit registers these custom filters in addition to Phalcon defaults:

- `md5`
- `json`
- `ipv4`
- `ipv6`

```php
$hash = $this->filter->sanitize($input, 'md5');
$json = $this->filter->sanitize($input, 'json');
$ipv4 = $this->filter->sanitize($input, 'ipv4');
```

Sanitizer behavior:

- `md5`: strips anything except lowercase hex characters.
- `json`: returns the input string only if `json_validate()` passes, otherwise
  returns null.
- `ipv4`: returns a valid IPv4 address or an empty string.
- `ipv6`: returns a valid IPv6 address or an empty string.

Rules:

- Use filters for input normalization and sanity checks.
- Use validators/model validation for business/domain rules.
- Do not rely on sanitizers alone for authorization or persistence rules.

## Validators

PhalconKit adds:

- `PhalconKit\Filter\Validation\Validator\Json`
- `PhalconKit\Filter\Validation\Validator\Color`

Example:

```php
$validation = new \PhalconKit\Filter\Validation();
$validation->add('data', new Json(['message' => 'not-valid-json']));
$validation->add('color', new Color(['message' => 'not-hex-color']));
$messages = $validation->validate($payload);
```

The JSON validator supports:

- `message`
- `template`
- `depth`
- `flags`
- `allowEmpty`

The color validator accepts 3, 4, 6, or 8 digit hex colors with `#`.

## Model Validation Helpers

For model validation helper coverage, read `model-behaviors.md`. Generated
abstract models use these helpers to mirror database columns, indexes, and enum
domains.

Typical concrete model pattern:

```php
public function validation(): bool
{
    $validator = $this->genericValidation();
    $this->addDefaultValidations($validator);
    $this->addJsonValidation($validator, 'data');
    return $this->validate($validator);
}
```

## REST Filter Conditions

REST controllers define allowed filter fields with `initializeFilterFields()`:

```php
$this->setFilterFields(new Collection([
    'id',
    'uuid',
    'UserEntity' => [
        'email',
        'firstName',
    ],
]));
```

The REST query condition layer validates allowed fields, normalizes operators,
creates bind keys, and builds conditions. It supports relationship-aware fields
and can generate correlated `EXISTS` or `NOT EXISTS` subqueries for relationship
filters.

For legacy compatibility, a null filter-field policy is unrestricted. An empty
collection is a closed policy that rejects every client filter. Prefer explicit
filter fields for new resources instead of relying on unrestricted filtering.
Filter/search enabled maps use boolean-like normalization: `yes`, `on`, and
`1` enable a key, while `off`, `false`, `no`, and `0` disable it. This matters
when policy values come from config or environment-driven merges.

Rules:

- Add fields to `filterFields` before accepting them from requests.
- Prefer nested arrays for relation fields when the app uses relation aliases.
- Use dynamic joins or joins only when the filter/search path requires them.
- Keep raw PHQL fragments out of controllers unless they are isolated in a
  named condition method.

## Search Conditions

REST controllers define searchable fields with `initializeSearchFields()`:

```php
$this->setSearchFields(new Collection([
    'label',
    'content',
    'UserEntity' => [
        'email',
    ],
]));
```

Use search fields for broad text-like search. Use filter fields for exact,
range, operator, or relation-specific filters.

## Identity, Permission, And Soft-Delete Conditions

REST controllers have default condition sets for:

- identity conditions
- permission conditions
- soft-delete conditions
- filter conditions
- search conditions

Permission config can attach behavior classes such as:

- `RemoveDefaultPermissionCondition`
- `RemoveDefaultSoftDeleteCondition`
- `RemoveDefaultSoftDeleteConditionWhileFiltering`

Only remove default conditions when the role/feature explicitly needs that
visibility.

## Advanced Condition Blocks

Custom advanced conditions should return the standard condition shape:

```php
[
    'conditions' => 'projectId = :projectId:',
    'bind' => ['projectId' => $projectId],
    'bindTypes' => ['projectId' => \Phalcon\Db\Column::BIND_PARAM_INT],
]
```

For complex conditions, use generated bind keys:

```php
$key = $this->generateBindKey('project_id');
$this->getConditions()->set('projectId', [
    'conditions' => 'projectId = :' . $key . ':',
    'bind' => [$key => $projectId],
    'bindTypes' => [$key => Column::BIND_PARAM_INT],
]);
```

Rules:

- Every bind key must be unique inside merged condition blocks.
- Use bind values and bind types for all request-derived values.
- Prefer `EXISTS` subqueries for relationship membership filters that would
  otherwise multiply rows through joins.
- Keep condition methods small and named after the business rule they enforce.
