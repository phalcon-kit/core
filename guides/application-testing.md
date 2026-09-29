# Test Your Application

Verify the behavior your users depend on: selected rows, persisted data, denied
access, response shape, and authentication continuity. A successful bootstrap or
static analysis run does not prove those workflows.

## Run The Skeleton Checks

From an App-based project:

```shell
composer phpunit
composer phpstan
composer phpcs
```

`composer qa` also validates Composer metadata, dependencies, and platform
requirements. Use your application's scripts; Core's internal test suite is not
a substitute for your own resource tests.

## Separate The Test Database

Use synthetic records in an isolated database. Load your application's migrations
and reset fixtures between scenarios. Use a different account/database from
production and verify its name before destructive fixture cleanup.

For the REST tutorial, the initial fixture is three projects: two active and one
draft, budget total 5000. Run write examples on a reset copy when checking exact
IDs and counts. Database-generated IDs and timestamps may vary.

## Test Through HTTP

For each resource, exercise [the scenario checklist](rest-scenarios.md):

1. Verify an authorized list returns the expected IDs and only exposed fields.
2. Create a record, reload it from the database, then update it by explicit ID.
3. Submit invalid input and prove the database did not accept it.
4. Repeat reads/writes as a guest and another user/tenant.
5. Test count, distinct, exports, and eager-loaded children with the same scope.
6. Check mixed batch success by examining every row result and database state.

Example shell assertion using `jq` on the original public Project fixture:

```shell
curl --fail-with-body --silent --show-error \
  'http://127.0.0.1:8080/api/project/find?order=id%20asc&count=1' \
  | jq -e '.code == 200 and .response == true and .view.count == 3
      and (.view.data | length) == 3
      and ([.view.data[] | has("deleted")] | any | not)'
```

`curl --fail-with-body` does not reject HTTP 207. Add an explicit assertion for
batch outcomes. Export checks should verify the downloaded file's contents and
encoding instead of parsing it as a REST envelope.

## Authentication Acceptance

With the same client cookie jar/token storage, verify login → current identity →
protected request → refresh → protected request → logout. Test invalid password,
expired/malformed access token, invalid refresh token, deleted account, and
another user's record. In stateful mode, dropping the cookie should not silently
retain authenticated identity. Test the browser over the same HTTPS/CORS setup
used in deployment.

For custom persistence, include concurrent refresh and identity cache changes.
For stateless mode, test your actual revocation policy rather than assuming
logout invalidates all previously issued JWTs.

## Models And Scaffolding

After a schema change, generate missing models, regenerate abstracts, inspect
the diff, then save/reload representative values through native Phalcon. Cover
nulls, booleans, decimals, enum values, and relationship ownership. Check that
concrete business methods survive regeneration.

Use unit tests for isolated business decisions and integration tests for ORM,
transaction, permission, and relation behavior. A mock returning the expected
payload cannot prove native persistence or query semantics.

## WebSockets And Workers

Check the starter ping/error protocol without starting a persistent listener
in ordinary unit tests. For an application subscription protocol, test forbidden
subscriptions, disconnect cleanup, two clients with different identities,
multi-worker delivery, duplicate events, restart/reconnect, and resynchronization.
See [Web Servers And WebSockets](web-server-and-websocket.md).
