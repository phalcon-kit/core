# Authentication

Add login, current identity, refresh, and logout to your application. This recipe
uses Core's standard user schema and PHP-session-backed identity. Application
registration, account approval, and password-reset delivery are separate workflows.

## Install The Identity Tables

For a new database, install the [Core baseline](database-migrations.md#fresh-core-installation)
before your project tables. It creates the user/role/group/type relations used
by current-user lookup, but creates no accounts or role data.

For a custom schema, map compatible models and preserve the relation aliases
required by identity; see [Feature Setup](feature-contracts.md).

## Configure A Signing Key

Generate a private key once per environment:

```shell
php -r 'echo "Aa1!" . bin2hex(random_bytes(64)), PHP_EOL;'
```

Put the result in `.env` as `SECURITY_JWT_PASSPHRASE="your-generated-value"`.
The random portion provides 64 bytes of entropy; the prefix satisfies Phalcon's
mixed-case/digit/symbol format check. A hex-only key fails that check even when
it is long. See the [Phalcon JWT builder reference](https://docs.phalcon.io/5.22/api/phalcon_encryption/).

Keep the key stable across requests/restarts and share it only between instances
of the same environment. A signing-key change invalidates issued tokens.

The default identity mode needs **both the bearer token and the session cookie**.
For this guide's loopback HTTP development server only, set:

```ini
SESSION_COOKIE_SECURE=0
```

Use secure cookies and HTTPS in deployment. Cross-origin browser clients also
need an explicit credentialed CORS policy and `credentials: 'include'`.

## Register The Auth Controller

Create `src/Modules/Api/Controllers/AuthController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Api\Controllers;

/** Authentication endpoints with a minimal public user representation. */
class AuthController extends \PhalconKit\Modules\Api\Controllers\AuthController
{
    public array $userExpose = [false, 'id', 'email'];
}
```

Add to `permissions.roles.everyone.components` in `src/Config.php`:

```php
\App\Modules\Api\Controllers\AuthController::class => [
    'login', 'get-identity', 'refresh', 'logout',
],
\PhalconKit\Models\User::class => ['find'],
```

The model grant permits the login lookup; it does not publish a User REST
controller. If you map User to an application model, grant that actual class.
Restrict any public user-resource endpoint separately. Add a method guard like
[the REST example](rest-api.md#enforce-http-methods): GET for `get-identity`, POST
for login/refresh/logout. Rate-limit authentication at the application/proxy.

## Create An Account With The CLI

The App skeleton registers Core's user task under its CLI module. Run it from
the application root, supplying a private password through stdin:

```shell
./bin/phalcon-kit cli user create editor@example.test --password-stdin < /path/to/private-password-file
```

Use a password manager or a protected local file as the input source; never
commit that file. The stdin option keeps the password out of shell history and
process arguments. Do not pass a positional password together with the option.

Expected output:

```json
{"errors":[],"save":1}
```

Check `save` and `errors`, not only the process exit code. Creating the same email
again fails model validation. Always supply a private password explicitly;
omitting password input invokes the task's convenience fallback and is unsuitable
for an account that will be used.

The standard Core User model hashes newly assigned plaintext before saving,
using the model's `hash()`/`checkHash()` pair. Existing recognized Phalcon password hashes
are preserved, including configured crypt formats. If your application maps a different User implementation, its
password validation/hashing hooks remain responsible for that behavior.

### Assign A Role

Role data and permission configuration are separate. Define the
`project-editor` grants from the [Project tutorial](first-rest-resource.md#7-add-protected-writes),
and ensure the role exists in the database. For the standard baseline, a small
application seed migration can create it:

```sql
INSERT INTO role (uuid, `key`, label)
VALUES (UUID(), 'project-editor', 'Project editor');
```

Run the seed once through your application migration/seed process, then assign:

```shell
./bin/phalcon-kit cli user role editor@example.test project-editor
```

Expected output is again `{"errors":[],"save":1}`. A result with `save: 0`
means no assignment was saved; check that both the user and role exist. The task
uses the user model's membership relation (`UserRoleList` in Core or an
application-defined `RoleNode`). No default account or role is installed for you.

### Change A Password

```shell
./bin/phalcon-kit cli user password editor@example.test --password-stdin < /path/to/private-password-file
```

The result is keyed by the configured user model class and includes `matched`,
`save`, and `errors`. A successful targeted change has `matched: 1`, `save: 1`,
and no errors. Stdin mode requires an explicit email to avoid resetting every
account to one password.

### Register The Task In A Custom Application

If your project supplies its own CLI module, create
`src/Modules/Cli/Tasks/UserTask.php`:

```php
<?php
namespace App\Modules\Cli\Tasks;

class UserTask extends \PhalconKit\Modules\Cli\Tasks\UserTask
{
}
```

Grant this class `['create', 'password', 'role', 'help']` under
`permissions.roles.cli.components`. Core's task registers the model-operation
permissions needed for account maintenance. Keep these commands restricted to
CLI access.

## Log In

The following requires `jq`. Put a login object in a private local `login.json`:

```json
{"email":"editor@example.test","password":"your-private-development-password"}
```

```shell
curl http://127.0.0.1:8080/api/auth/login \
  -c cookies.txt -b cookies.txt \
  -H 'Content-Type: application/json' \
  --data-binary @login.json > login-response.json
API_TOKEN=$(jq -r '.view.jwt' login-response.json)
REFRESH_TOKEN=$(jq -r '.view.refreshToken' login-response.json)
jq '{code, response, loggedIn: .view.loggedIn, user: .view.user}' login-response.json
```

Expected redacted projection:

```json
{"code":200,"response":true,"loggedIn":true,"user":{"id":1,"email":"editor@example.test"}}
```

The full `view` also includes `jwt`, `refreshToken`, `refreshed`, `loggedInAs`,
`userAs`, `messages`, and role/group/type lists. IDs vary. Treat response files,
login input, and the cookie jar as secrets and remove them after local testing.
A token pair by itself does not prove login succeeded: check `loggedIn` and HTTP
status. Failed login returns HTTP 401 with messages.

Do not send an expired/invalid access token with login. Authorization can reject
it before credential processing.

## Use The Identity

```shell
curl http://127.0.0.1:8080/api/auth/get-identity \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN"
```

Expected: HTTP 200, `response: true`, `view.loggedIn: true`. Your role membership
appears under `view.roleList`; the application config's `project-editor` grants
control permitted actions. A role row without config grants does not authorize
your resource.

Include the same cookie jar and bearer header on protected Project requests:

```shell
curl http://127.0.0.1:8080/api/project/create \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' \
  --data '{"label":"Playground","status":"draft","budget":500}'
```

## Refresh

Send the refresh credential without an expired access-token header:

```shell
jq -n --arg refreshToken "$REFRESH_TOKEN" '{refreshToken: $refreshToken}' > refresh.json
curl http://127.0.0.1:8080/api/auth/refresh \
  -b cookies.txt -c cookies.txt \
  -H 'Content-Type: application/json' \
  --data-binary @refresh.json > refresh-response.json
API_TOKEN=$(jq -r '.view.jwt' refresh-response.json)
REFRESH_TOKEN=$(jq -r '.view.refreshToken' refresh-response.json)
```

On success, HTTP 200 includes a new pair and `view.refreshed: true`. Replace both
stored tokens and accept the renewed cookie. Session-backed refresh rotates the
identity storage key. Rejected refresh returns HTTP 401; discard unusable client
credentials and require login.

## Log Out

```shell
curl http://127.0.0.1:8080/api/auth/logout \
  -b cookies.txt -c cookies.txt \
  -H "X-Authorization: Bearer $API_TOKEN" \
  -H 'Content-Type: application/json' --data '{}'
```

Expected: `response: true`, `view.loggedIn: false`. Discard client tokens and
cookies. A subsequent identity request must not regain the prior identity.

## Other Authentication Modes And Actions

| Need | Application setup |
| --- | --- |
| Standard `Authorization` header | Set `IDENTITY_AUTHORIZATION_HEADER=Authorization`; update CORS/proxy forwarding |
| Bearer-only identity | Set `IDENTITY_STATELESS=true`; tokens carry identity, with application-owned revocation |
| Custom database/session persistence | Extend the identity service and implement persistence/rotation; setting an adapter label does not implement storage |
| Password reset | Grant `reset-password` after configuring templates, delivery, token lifetime, and password hashing |
| Impersonation | Grant `login-as`/`logout-as` only to an administrative role; send `userId` and test return-to-original-user behavior |
| Registration | Implement account validation, hashing, approval, and role assignment in your application |

In stateless mode, logout does not revoke an already issued token by itself.
Use short lifetimes and an explicit revocation strategy when required. Stateful
and stateless token validation both reject invalid credentials with the generic
message `Invalid authentication token.`. Keep tokens out of query strings/logs.

For row access, role inheritance, and custom persistence boundaries, see
[Identity And Permissions](identity-and-permissions.md).
