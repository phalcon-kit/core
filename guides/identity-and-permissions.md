# Identity And Permissions

Use identity to establish who is making a request; use permissions and query
conditions to decide what that identity may do and which records it may see.
Start with [Authentication](authentication.md) for a complete login setup.

## Grant Actions And Model Operations

The dispatcher authorizes a controller/action. Model security separately
checks database operations. A controller grant alone is insufficient.

In `src/Config.php`, group reusable permissions into features:

```php
'permissions' => [
    'features' => [
        'readProjects' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    'find', 'find-with', 'find-first', 'find-first-with', 'count',
                ],
                \App\Models\Project::class => ['find', 'count'],
                \App\Models\Task::class => ['find'],
            ],
        ],
        'editProjects' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    'create', 'update', 'delete',
                ],
                \App\Models\Project::class => ['find', 'create', 'update', 'delete'],
            ],
        ],
    ],
    'roles' => [
        'project-reader' => ['features' => ['readProjects']],
        'project-editor' => ['features' => ['readProjects', 'editProjects']],
    ],
],
```

Use concrete class constants and dash-case action names. `find-with` corresponds
to `findWithAction()`. A wildcard action grant exposes all actions on that
component, including custom actions you add later.

Built-in context roles include `everyone`, `guest`, `cli`, and `ws`. `everyone`
is a public grant. `cli` and `ws` identify runtime context, not an authenticated
WebSocket user. Keep connection-level WebSocket authorization separate.

Stored roles need membership records and matching configuration grants. Stored
feature/role association tables are not an automatic replacement for the
configuration permission graph. See [Feature Setup](feature-contracts.md).

## Restrict Rows

Default REST query conditions use `createdBy` ownership and `deleted = 0`.
The default `admin`/`dev` super roles bypass the owner condition. A resource
whose schema uses another ownership model must define it explicitly.

For a resource with `ownerId`:

```php
public function getCreatedByColumns(): array
{
    return ['ownerId'];
}
```

For a deliberately public catalogue without ownership, return `[]` as in the
first tutorial. That removes the default owner restriction, so use it only when
all rows visible under the remaining conditions are meant to be shared.

For tenant isolation, replace the default permission condition with a bound,
server-derived tenant scope. This example assumes an **application-provided**
`tenantContext` service with `requireTenantId()` that throws unless identity has
an authorized active tenant:

```php
public function initializePermissionConditions(): void
{
    $tenantId = $this->di->getShared('tenantContext')->requireTenantId();
    $field = $this->appendModelName('tenantId');
    $this->setPermissionConditions([
        'tenant' => [
            $field . ' = :activeTenant:',
            ['activeTenant' => $tenantId],
            ['activeTenant' => \Phalcon\Db\Column::BIND_PARAM_INT],
        ],
    ]);
}
```

`tenantContext` is an application service, not a built-in Core provider. Do not
read the tenant ID directly from a client filter and treat it as authorization.
For tenant-owned writes, set the tenant from that same service and remove it
from writable input fields. Cover read, update, delete, count, export, and nested
relations with the same access rule.

## Field Policies Are Separate

- Expose policy determines which values leave the API.
- Save policy determines what a client may assign.
- Filter/order policy determines which attributes may influence queries.
- With policy determines the allowed relationship graph.
- Row conditions determine which records participate.

A field can be hidden in JSON yet still leak information through unrestricted
filters or aggregate grouping. Define each policy deliberately. Use
`setExposeFields([false, ...])` to hide unspecified fields.

Permission behaviors can remove individual default conditions for a configured
feature. Prefer narrowly scoped overrides; removing all permission conditions
as a fix for an empty result can expose another user's data.

## Identity State And Custom Persistence

The default identity stores an authenticated payload in the PHP session under
the JWT claim key. Login and authenticated refresh renew the session ID. Clients
must retain the replacement cookie. Session fallback is disabled by default;
a session cookie alone is not the normal bearer-token lookup contract.

Stateless mode stores identity in signed claims. It avoids identity session
persistence but does not supply server-side token revocation. Deleted users are
rejected when the identity resolves the current user.

Custom `setSessionIdentity()` or `removeSessionIdentity()` implementations must
clear cached identity and model ACL roles through `clearIdentityCache()`. Own
credential rotation, concurrent refresh, expiration, and revocation in the custom
store. Preserve token validation before trusting claims or touching storage.

For long-lived workers, initialize and clear authorization context for each
logical request/message. A singleton identity from one connection must not
become another connection's authority.

## Diagnose Access Failures

| Symptom | Check |
| --- | --- |
| 404 for an implemented action | Concrete component/action registered in permissions; correct module namespace |
| 401 with a token | Header name, signing configuration, expiration, cookie persistence for stateful mode |
| 403 on one selector | Filter/order/with/count policy and actual selector spelling |
| Empty list despite table rows | Model `find` grant, owner/tenant condition, soft-delete condition, bound input |
| Read works but save fails | Model create/update grant, field policy, validation messages, related model grants |
| Role membership missing | Actual relation alias, membership rows, mapped model compatibility, identity cache |

Test a guest, the intended user, another user/tenant, and an administrator.
Verify denied operations against database state, not only response status.
