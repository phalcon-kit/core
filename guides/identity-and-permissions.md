# Identity And Permissions

Phalcon Kit identity is integrated with sessions, JWT, ACL roles, impersonation,
controller permissions, model permissions, CLI tasks, and WebSocket tasks.

Official Phalcon references:

- ACL: https://docs.phalcon.io/latest/acl/
- Security: https://docs.phalcon.io/latest/encryption-security/
- JWT: https://docs.phalcon.io/latest/encryption-security-jwt/
- Sessions: https://docs.phalcon.io/latest/session/

Authorization is evaluated in layers:

```text
authenticated identity and context roles
    ↓
feature grants for controller/action/model
    ↓
optional behavior overrides
    ↓
row-level permission conditions
    ↓
query and response
```

A broader feature grant should not be used to repair an incorrect row
condition. Diagnose the layer that denied access.

## Identity Responsibilities

The identity service handles:

- session-backed login state
- JWT generation and refresh
- current user lookup
- impersonation
- role lists and inherited roles
- context roles such as `guest`, `cli`, `ws`, and `everyone`
- ACL role expansion for permission checks

Application auth controllers can extend the core auth controller and customize
workflow-specific login paths.

```php
final class AuthController extends \PhalconKit\Modules\Api\Controllers\AuthController
{
    public function loginParticipantAction(): bool
    {
        $this->view->setVars($this->identity->getJwt());
        $this->view->setVars($this->loginParticipant($this->getLoginParams()));
        $this->view->setVars($this->identity->getIdentity($this->userExpose));

        $loggedIn = $this->view->getVar('loggedIn') ?? false;
        if (!$loggedIn) {
            $this->setStatusCode(401);
        }

        return $loggedIn;
    }
}
```

Keep unusual login behavior in the app controller while reusing identity,
validation, messages, and response behavior from the core.

## JWT Validation And Upgrades

For the additional unreleased signing-key, identity, CORS, and query protections,
read the [security hardening upgrade notes](security-hardening.md).

Starting with the 3.10.6 security fix, identity rejects invalid JWTs
with `PhalconKit\Exception\HttpException` (401) and the public message
`Invalid authentication token.`. Earlier identity code called `validateToken()`
but discarded its error array, allowing invalid subjects to reach session
lookup and refresh rotation.

The validation boundary is shared by access authentication and refresh:

- Access authentication resolves `jwt` or a bearer token from the configured
  authorization header, then validates before reading the subject or looking
  up `userId`/`asUserId` in session storage.
- `getJwt(true)` force-resolves the refresh credential before reading, removing,
  or copying the existing identity. A rejected refresh returns no token pair
  and leaves persisted identity and any previously resolved claim unchanged.
- Invalid credentials never fall through to PHP session authentication.
  Malformed non-empty values are rejected without string sanitization. Missing,
  null, or empty-string token fields retain the existing no-token behavior;
  unsupported authorization schemes still produce no claim.
- Access and refresh token ids remain distinct. A refresh token supplied as
  `jwt`/bearer, or an access token supplied as `refreshToken`, is rejected.
  The existing source precedence is unchanged: when `refreshToken` is absent,
  `getJwt(true)` can still resolve a valid access credential or the configured
  session fallback. This change does not introduce a refresh-token-only policy.
- Stateful and stateless modes enforce the same checks. Numeric date claims
  must be JSON numbers; malformed subject JSON and non-string subjects are
  rejected. Missing subjects and valid JSON scalar subjects retain their
  previous empty-claim behavior.

The low-level `Provider\Jwt\Jwt::validateToken()` contract is unchanged: an
empty error array means validation passed, and every non-empty result must be
rejected by the caller. It can also throw for malformed input. Custom identity
implementations overriding `getClaimFromToken()` must preserve this enforcement.
Overrides of session persistence alone inherit the fix.

Consumer rollout:

1. Upgrade `phalcon-kit/core` through Composer to the fixed release and commit
   the application lockfile. Do not patch the installed vendor directory.
2. Check custom identity overrides and HTTP exception handlers. Use the normal
   401 response without including token strings, validator diagnostics, or
   request debug dumps. Custom API error controllers must avoid re-entering
   identity resolution while rendering a rejected credential.
3. Send `refreshToken` to the refresh endpoint without an expired access JWT in
   the request/header: dispatch authorization may validate access credentials
   before the refresh action runs. On refresh rejection, discard unusable
   client credentials and require login. Login requests must also omit stale
   invalid JWTs because the core login action resolves tokens first.
4. Verify valid login/access/refresh/logout and impersonation in staging, plus
   expired access and refresh rejection using the application's persistence
   override. Replace stored tokens after successful stateless identity changes.

Token lifetimes, clock allowances, idle and absolute session durations, and
stateless revocation behavior are unchanged. Existing valid tokens keep their
format and signing configuration; tokens previously accepted despite validation
errors now fail. This fix does not revoke otherwise valid tokens or delete
stored sessions. The package tests need no live application or database:

```bash
composer phpunit -- --filter JwtValidationTest
```

## Permission Config

Permissions are config-driven. Features group component permissions and optional
controller behaviors. Roles receive features.

```php
'permissions' => [
    'features' => [
        'manageVote' => [
            'components' => [
                \App\Modules\Api\Controllers\VoteController::class => ['*'],
                \App\Models\Vote::class => ['*'],
            ],
            'behaviors' => [
                \App\Modules\Api\Controllers\VoteController::class => [
                    \PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultPermissionCondition::class,
                ],
            ],
        ],
        'viewVote' => [
            'components' => [
                \App\Modules\Api\Controllers\VoteController::class => [
                    'find',
                    'find-with',
                    'find-first',
                    'find-first-with',
                ],
                \App\Models\Vote::class => ['find'],
            ],
        ],
    ],
    'roles' => [
        'admin' => [
            'features' => ['manageVote'],
        ],
        'participant' => [
            'features' => ['viewVote'],
        ],
    ],
],
```

Components can be controllers, controller actions, models, model methods, CLI
tasks, or WebSocket tasks.

Use controller class names as the canonical component key. Route-style
controller aliases such as `project-user` are accepted during dispatcher
security checks for compatibility, but class constants are easier to refactor.

Use dash-case for action keys because these names match URLs:

```php
\App\Modules\Api\Controllers\ProjectController::class => [
    'find',
    'find-with',
    'archive-project',
],
```

Existing camelCase action config remains valid. Phalcon Kit normalizes dispatcher
actions and ACL registrations so `findWith` and `find-with` refer to the same
permission action. New docs and generated examples should prefer dash-case.

## Controller Attributes

Controllers may declare permissions with PHP attributes. Attributes are additive:
they compile into the same `permissions` structure shown above, so existing
config-driven features, roles, and inheritance remain valid.

```php
use Phalcon\Http\ResponseInterface;
use PhalconKit\Mvc\Controller\Attributes\AllowRoles;
use PhalconKit\Mvc\Controller\Attributes\AttachBehavior;
use PhalconKit\Mvc\Controller\Attributes\PermissionFeature;
use PhalconKit\Mvc\Controller\Behavior\Query\Conditions\RemoveDefaultPermissionCondition;

#[PermissionFeature('project.view', actions: ['find', 'find-with'])]
#[AllowRoles('admin', actions: '*')]
final class ProjectController extends AbstractController
{
    #[PermissionFeature('project.write')]
    #[AllowRoles(['admin', 'manager'])]
    #[AttachBehavior(RemoveDefaultPermissionCondition::class, roles: 'admin')]
    public function archiveProjectAction(): ResponseInterface
    {
        // ...
    }
}
```

Method-level attributes without `actions` use the method name after removing the
`Action` suffix. In the example above, `archiveProjectAction()` maps to
`archive-project`. Class-level attributes without `actions` apply to `*`.

`PermissionFeature` declares which actions belong to a feature; roles still
receive that feature through config:

```php
'permissions' => [
    'roles' => [
        'manager' => [
            'features' => ['project.write'],
        ],
    ],
],
```

`AllowRoles` grants controller actions directly to roles when a small app or a
local workflow does not need a reusable feature. `AttachBehavior` can target
roles, features, or both. When neither `roles` nor `features` is provided, the
behavior attaches for the `everyone` context role.

### Config-First Feature Example

Use this style when permissions are owned centrally and several controllers,
models, tasks, or WebSocket actions share the same feature.

```php
'permissions' => [
    'features' => [
        'project.view' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    'find',
                    'find-with',
                    'find-first',
                    'find-first-with',
                ],
                \App\Models\Project::class => ['find'],
            ],
        ],
        'project.manage' => [
            'components' => [
                \App\Modules\Api\Controllers\ProjectController::class => ['*'],
                \App\Models\Project::class => ['*'],
            ],
            'behaviors' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    RemoveDefaultPermissionCondition::class,
                ],
            ],
        ],
    ],
    'roles' => [
        'admin' => [
            'features' => ['project.manage'],
        ],
        'researcher' => [
            'features' => ['project.view'],
        ],
    ],
],
```

### Attribute-First Feature Example

Use this style when the controller owns its own action surface but roles should
still receive reusable features through config.

```php
#[PermissionFeature('project.view', actions: [
    'find',
    'find-with',
    'find-first',
    'find-first-with',
])]
#[PermissionFeature('project.manage', actions: '*')]
#[AttachBehavior(RemoveDefaultPermissionCondition::class, features: 'project.manage')]
final class ProjectController extends AbstractController
{
    #[PermissionFeature('project.manage')]
    public function archiveProjectAction(): ResponseInterface
    {
        // archive-project
    }
}
```

The remaining config only assigns features to roles:

```php
'permissions' => [
    'roles' => [
        'admin' => [
            'features' => ['project.manage'],
        ],
        'researcher' => [
            'features' => ['project.view'],
        ],
    ],
],
```

### Direct Role Attribute Example

Use direct role attributes for small controllers or local actions that are not
worth turning into global feature names.

```php
final class ProjectController extends AbstractController
{
    #[AllowRoles(['admin', 'manager'])]
    public function archiveProjectAction(): ResponseInterface
    {
        // archive-project
    }

    #[AllowRoles('admin', actions: ['restore', 'force-delete'])]
    public function restoreAction(): ResponseInterface
    {
        // restore
    }
}
```

Direct role attributes do not require matching `features` config. The role still
has to be present in the current identity's ACL role list.

### Behavior Example In Both Styles

Action-scoped behavior through config:

```php
'permissions' => [
    'roles' => [
        'admin' => [
            'behaviorActions' => [
                \App\Modules\Api\Controllers\ProjectController::class => [
                    'archive-project' => [
                        RemoveDefaultPermissionCondition::class,
                    ],
                ],
            ],
        ],
    ],
],
```

The same behavior beside the action:

```php
final class ProjectController extends AbstractController
{
    #[AttachBehavior(RemoveDefaultPermissionCondition::class, roles: 'admin')]
    public function archiveProjectAction(): ResponseInterface
    {
        // archive-project
    }
}
```

The existing `behaviors` key is still supported for non-action-scoped behavior
attachment.

Controller attributes use PHP's built-in Reflection API. Supported PHP builds
include Reflection; no extra Composer package or optional extension is required.
Only the active controller class is inspected, and Phalcon Kit caches the result
inside the process. Config-only applications can disable attribute scanning with
`ACL_ATTRIBUTES=false` or config:

```php
'acl' => [
    'attributes' => false,
],
```

## Row-Level Conditions

Feature-level access answers whether a role may use a component. Row-level
conditions answer which records that role may see or change.

Use controller permission conditions for resource-specific scoping:

```php
public function initializePermissionConditions(): void
{
    parent::initializePermissionConditions();

    $this->getPermissionConditions()->set(
        'projectId',
        $this->getProjectIdPermissionCondition('projectId')
    );
}
```

Super roles can bypass row restrictions when the resource explicitly allows it.
Keep that decision local to the resource controller.

## Behavior Overrides

Permission features can attach controller behaviors such as:

- removing the default permission condition for admin-only management screens
- allowing soft-deleted rows while filtering
- changing query behavior for a specific role/feature

Use behavior overrides sparingly. Most resources should keep the default
permission and soft-delete conditions.

## Practical Rules

- Put policy in config, not scattered conditionals.
- Put record ownership rules in controller permission conditions.
- Use role inheritance for broad policy, not copied feature lists.
- Keep guest, participant, CLI, and WebSocket permissions explicit.
- Do not use public issues for security defects. Follow `SECURITY.md`.

Continue with [Configuration](configuration.md) for the complete permission
graph, [REST APIs](rest-api.md) for query integration, and
[Troubleshooting](troubleshooting.md) for access-denied and missing-row symptoms.
