# REST Requests And Responses

Examples use the [Project resource](first-rest-resource.md) at
`http://127.0.0.1:8080`. Authentication examples use the default header
`X-Authorization: Bearer <access-token>`.

## Where Parameters Come From

| HTTP method | Input consumed by the REST parameter helpers |
| --- | --- |
| POST, PUT, PATCH | JSON body for `application/json` or a `+json` media type; otherwise form body |
| GET, DELETE, other methods | Query string |

Body and query parameters are not merged. Put update identity **in the body**:

```shell
curl -X PATCH http://127.0.0.1:8080/api/project/update \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"id":1,"label":"Community garden expansion"}'
```

A DELETE uses query parameters:

```shell
curl -X DELETE 'http://127.0.0.1:8080/api/project/delete?id=1' \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN"
```

For filters, use bracket-encoded query arrays or a JSON array in a JSON body.
A JSON string inside a query parameter is not decoded into a filter array.
See [Filtering](rest-filtering.md). The internal `_url` rewrite parameter is
removed from REST input.

The generic input helper is not a JSON schema validator. Validate required
fields, accepted shapes, and limits for custom endpoints. In particular, do not
interpret HTTP 200 from an empty save batch as proof that a record was created.

## The JSON Envelope

A successful list returns:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "OK",
  "code": 200,
  "response": true,
  "view": {
    "data": [
      {"id": 1, "label": "Community garden", "status": "active", "budget": 1200}
    ]
  }
}
```

| Field | Meaning |
| --- | --- |
| `timestamp` | Server response time, including timezone offset |
| `status` | HTTP reason phrase |
| `code` | HTTP status code; also check the actual HTTP status |
| `response` | Action result, often boolean; may be null or another value |
| `view` | Action-specific data and metadata |
| `debug` | Optional development diagnostics when debugging is enabled |

`view.data` is a list for `find`, an object for `find-first`, and an exposed saved
record for a successful write. The envelope does not use `success` or a top-level
`data` field. An empty view serializes as `[]`; clients should tolerate it.

## Empty And Missing Results

No list matches, HTTP 200:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "OK",
  "code": 200,
  "response": true,
  "view": {"data": []}
}
```

No single-record match, HTTP 404:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "Not Found",
  "code": 404,
  "response": null,
  "view": []
}
```

A row hidden by ownership or soft-delete conditions is also a non-match. Do not
assume a 404 proves the database contains no row with that ID.

## Validation Errors

Creating a duplicate `label` in the tutorial returns HTTP 422:

```json
{
  "timestamp": "2026-09-29T10:00:00-04:00",
  "status": "Unprocessable Entity",
  "code": 422,
  "response": false,
  "view": {
    "saved": false,
    "messages": [
      {
        "field": "label",
        "message": "not-unique",
        "type": "Phalcon\\Filter\\Validation\\Validator\\Uniqueness",
        "code": 0,
        "metaData": []
      }
    ]
  }
}
```

`messages[].code` is a model message code, not necessarily the HTTP status.
Applications may supply translated messages, field lists, metadata, and custom
message types. Distinct-field errors use a string message list; custom error
controllers may use another shape. Normalize messages in your client instead
of assuming every entry is an object.

## Status Reference

| Status | Typical cause |
| --- | --- |
| 200 | Read, successful update/save/delete/restore/reorder, or all-success batch |
| 201 | Single successful explicit `create` |
| 207 | Batch contains both saved and failed rows; `response` is false |
| 304 | Conditional cached response, when enabled and validator matches; no body |
| 400 | Missing update identity, invalid input, excessive limit, invalid distinct field |
| 401 | Rejected authentication token or failed login |
| 403 | Disallowed filter/order/relationship/count option, or denied permission |
| 404 | Missing/hidden row, unknown route/component, or unavailable update target |
| 405 | Your application's method guard rejects the HTTP verb |
| 422 | Validation failure or a batch in which every row failed |
| 500 | Application/configuration/database failure or unsupported model behavior |

Permission forwarding can produce 401, 403, or 404 depending on component
registration, roles, and configured error routes. Inspect the status and message;
do not promise that all permission failures share one status.

## JavaScript Client

This helper deliberately checks both HTTP status and the envelope: HTTP 207 is
`ok` to `fetch`, but its failed rows still need attention.

```javascript
async function api(path, {token, ...options} = {}) {
  const headers = new Headers(options.headers);
  if (token) headers.set('X-Authorization', `Bearer ${token}`);
  const response = await fetch(`/api/${path}`, {...options, headers});
  const payload = await response.json();
  if (response.status === 207) return {partial: true, payload};
  if (!response.ok || payload.response === false) {
    throw Object.assign(new Error(payload.status || 'Request failed'), {
      status: response.status,
      messages: payload.view?.messages ?? [],
      payload,
    });
  }
  return {partial: false, payload};
}

const query = new URLSearchParams({order: 'id asc', limit: '20', count: '1'});
query.set('filters[0][field]', 'status');
query.set('filters[0][operator]', '=');
query.set('filters[0][value]', 'active');
const {payload} = await api(`project/find?${query}`);
console.log(payload.view.data, payload.view.count);
```

Handle network errors separately. Do not use this JSON helper for exports or
304 responses, which have a different body contract. Choose token storage based
on your application's browser/XSS threat model; avoid placing bearer credentials
in URLs or logs.

## Caching And Cross-Origin Clients

Response caching is optional. Configure it deliberately and test identity
isolation before enabling it on authenticated endpoints. Debug responses may
include sensitive internals; keep `APP_DEBUG=false` outside local development.

For a frontend on another origin, allow its explicit origin and the configured
authorization header in CORS. Cookie-based cross-origin requests also need
credentials on both client and server. See [Application Security](security-hardening.md).
