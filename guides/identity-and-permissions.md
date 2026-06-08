# Identity And Permissions

PhalconKit identity is integrated with sessions, JWT, ACL roles, impersonation,
controller permissions, model permissions, CLI tasks, and WebSocket tasks.

Official Phalcon references:

- ACL: https://docs.phalcon.io/5.14/acl/
- Security: https://docs.phalcon.io/5.14/encryption-security/
- JWT: https://docs.phalcon.io/5.14/encryption-security-jwt/
- Sessions: https://docs.phalcon.io/5.14/session/

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
