# PhalconKit Identity, Auth, And Security

Use this reference when changing authentication controllers, identity provider
overrides, JWT/session behavior, impersonation, role checks, ACL permission
config, or security behaviors in a PhalconKit application.

## Phalcon Baseline

Native Phalcon references:

- ACL: https://docs.phalcon.io/5.13/acl/
- Security and password hashing: https://docs.phalcon.io/5.13/encryption-security/
- JWT: https://docs.phalcon.io/5.13/encryption-security-jwt/
- Sessions: https://docs.phalcon.io/5.13/session/
- Controllers: https://docs.phalcon.io/5.13/controllers/

PhalconKit identity uses native Phalcon ACL, security, session, request, and
controller services as its base. Use native docs for raw service behavior and
this file for the PhalconKit identity manager, JWT/session identity,
impersonation, role inheritance, and permission-policy model.

## Core Identity Flow

The API auth controller is intentionally thin. Core
`PhalconKit\Modules\Api\Controllers\AuthController` extends the REST controller
and uses `AuthActions` for:

- `getIdentityAction()`
- `getJwtAction()` and `refreshAction()`
- `loginAction()`
- `loginAsAction()` for impersonation
- `logoutAction()`
- `logoutAsAction()`
- `resetPasswordAction()`

The controller delegates state to the `identity` DI service. The identity
manager handles:

- Validating login/reset inputs.
- Finding users through configured model aliases.
- Creating and refreshing JWT and refresh tokens.
- Reading JWT from request body, query parameters, or the configured
  authorization header.
- Storing session identity under the token claim key.
- Resolving the current user with eager-loaded `RoleList`, `GroupList`, and
  `TypeList`.
- Checking roles and inherited roles.
- Resolving effective ACL roles for HTTP, CLI, and WebSocket contexts.
- Impersonating another user through `userId` and `asUserId`.

`getIdentity()` returns identity state and exposed user data:

- `loggedIn`: current effective user is logged in.
- `user`: current effective user, exposed through `userExpose` when provided.
- `loggedInAs`: impersonation/original-user state is active.
- `userAs`: original user while impersonating.
- `roleList`, `groupList`, `typeList`: keyed relation lists from the current
  user.

Do not use `loggedInAs` to mean "logged in as participant" or any other domain
role. In PhalconKit it means the session is impersonating another user.

## Auth Controller Customization

Extend the core auth controller when an app needs an additional login flow but
still wants core JWT, identity, and REST response behavior.

Example: allow users without passwords to log in only through a participant
endpoint, while users with passwords must use the normal login endpoint.

```php
namespace App\Modules\Api\Controllers;

use Phalcon\Filter\Validation\Validator\Email;
use Phalcon\Filter\Validation\Validator\PresenceOf;
use Phalcon\Messages\Message;
use PhalconKit\Filter\Validation;

class AuthController extends \PhalconKit\Modules\Api\Controllers\AuthController
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

    public function loginParticipant(?array $params = null): array
    {
        $validation = new Validation();
        $validation->add('email', new PresenceOf(['message' => 'required']));
        $validation->add('email', new Email(['message' => 'email-not-valid']));
        $validation->validate($params);

        $messages = $validation->getMessages();
        if (!$messages->count()) {
            $user = $this->identity->findUserByEmail($params['email']);

            $loginFailed = new Message('Login Failed', ['email'], 'LoginFailed', 401);
            $loginForbidden = new Message('Login Forbidden', ['email'], 'LoginForbidden', 403);
            $wrongEndpoint = new Message(
                'Wrong Login Endpoint',
                ['email', 'password'],
                'WrongLoginEndpoint',
                403
            );

            if (!isset($user)) {
                $validation->appendMessage($loginFailed);
            }
            elseif ($user->isDeleted()) {
                $validation->appendMessage($loginForbidden);
            }
            elseif (!empty($user->getPassword())) {
                $validation->appendMessage($wrongEndpoint);
            }
            elseif (!$this->hasOnlyParticipantLoginRoles($user)) {
                $validation->appendMessage($wrongEndpoint);
            }
            else {
                $this->identity->setSessionIdentity([
                    'userId' => $user->getId(),
                ]);
            }
        }

        return [
            'loggedIn' => $this->identity->isLoggedIn(false, true),
            'loggedInAs' => $this->identity->isLoggedIn(true, true),
            'messages' => $validation->getMessages(),
        ];
    }

    private function hasOnlyParticipantLoginRoles($user): bool
    {
        $allowed = ['participant', 'visitor', 'employee'];
        $roleList = $user->getRoleList();

        if (!count($roleList)) {
            return false;
        }

        foreach ($roleList as $role) {
            if (!in_array($role->getKey(), $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}
```

Rules for custom auth endpoints:

- Keep core `loginAction()` unchanged for password users.
- Keep custom endpoint validation explicit and small.
- Always block deleted users.
- If a user has a password, force the normal login endpoint.
- Restrict passwordless domain flows to narrow roles or another explicit
  business rule.
- Set identity through `$this->identity->setSessionIdentity(['userId' => ...])`
  after validation succeeds.
- Call `$this->identity->getJwt()` before setting identity when following the
  core action pattern, so the token claim key exists.
- Return core identity keys consistently; add a separate domain key if the UI
  needs to know that a participant login flow was used.
- Use `Message` codes and HTTP-like status codes so REST responses stay
  machine-readable.

## JWT And Session Identity

The JWT claim contains a generated `key`. Session identity is stored behind
that key. This means a token identifies a session bucket, and the bucket stores
values such as:

```php
['userId' => 123]
['userId' => 456, 'asUserId' => 123]
```

Core behavior:

- `getJwt()` creates a new key when none exists.
- `refreshAction()` refreshes the claim key and can move the current session
  identity to the new key.
- JWT is read from `jwt`, refresh token from `refreshToken`, or the configured
  authorization header, usually `X-Authorization`.
- `identity.sessionFallback` can use PHP session storage as fallback, but avoid
  enabling it unless the app needs that compatibility mode.

Apps can override the identity provider to persist session identity somewhere
else, such as an app `Session` model. The provider must still register the
service as `identity` and return an identity manager with the DI injected.

## Impersonation

Core impersonation stores both ids:

```php
[
    'userId' => $targetUserId,
    'asUserId' => $originalUserId,
]
```

`loginAsAction()` delegates to `identity->loginAs()`. Core login-as currently
allows `admin` or `dev` roles, validates `userId`, and resolves the target user.
`logoutAsAction()` returns the session to `asUserId`.

When customizing impersonation:

- Keep `asUserId` as the original user.
- Keep `userId` as the effective/current user.
- Do not overload `loggedInAs` for domain roles.
- Prefer moving authorization into config permissions if the app needs a more
  granular impersonation policy.

## Roles, Inheritance, And ACL

`identity->hasRole()` checks the current identity roles and, by default,
includes inherited roles from config:

```php
if ($this->identity->hasRole(['admin'])) {
    // admin-only path
}
```

`identity->getAclRoles()` builds the effective ACL role set used for permission
checks:

- Adds `ws` in WebSocket bootstrap context.
- Adds `cli` in CLI bootstrap context.
- Always adds `everyone`.
- Uses identity roles, or `guest` when no identity roles exist.
- Adds inherited roles from `permissions.roles.<role>.inherit`.

Use root config for stable role inheritance:

```php
'permissions' => [
    'roles' => [
        'visitor' => [
            'inherit' => ['participant'],
        ],
        'admin' => [
            'inherit' => ['user'],
        ],
    ],
],
```

## Permission Policy And Security Behavior

PhalconKit's security middleware/behavior layer attaches controller and model
behaviors from config before route execution. The behavior trait reads
`permissions.roles`, expands role features from `permissions.features`, checks
the current identity roles, then attaches configured behavior classes for the
matching controller or model.

Permission config can grant access to:

- Controllers and actions.
- Models and model methods.
- CLI tasks.
- WebSocket tasks.
- Behavior classes that change query conditions, fields, or lifecycle behavior.

For compact manage/view/CLI/WS/submission feature examples, read the permission
section in `references/configuration.md`.

Feature example:

```php
'permissions' => [
    'features' => [
        'viewEvent' => [
            'components' => [
                EventController::class => [
                    'find',
                    'find-with',
                    'find-first',
                    'find-first-with',
                ],
                Event::class => ['find', 'findFirst'],
            ],
            'behaviors' => [
                EventController::class => [
                    RemoveDefaultPermissionCondition::class,
                ],
            ],
        ],
    ],
    'roles' => [
        'participant' => [
            'features' => ['viewEvent'],
        ],
    ],
],
```

Security guidance:

- Prefer config policies over hard-coded controller checks when the rule is
  about component/action/model access.
- Keep controller checks for request-specific or domain-specific cases.
- Be careful with `RemoveDefaultPermissionCondition` and related condition
  removers. They change row visibility.
- Keep auth endpoints and permission features synchronized. A role that can log
  in must also have the minimum components/actions it needs after login.
- When adding a new auth action, add a permission config entry for that action.

## Agent Checklist

When changing auth or security:

1. Inspect the app `AuthController`, identity provider override, root config
   `providers`, root config `permissions`, and relevant permission fragments.
2. Preserve core `login`, `logout`, `refresh`, `login-as`, and reset semantics
   unless the task explicitly changes them.
3. Keep custom login endpoints narrow and role-gated.
4. Use `setSessionIdentity()` only after validation and authorization pass.
5. Treat `loggedInAs` as impersonation state, not a domain role.
6. Verify role inheritance and effective ACL roles.
7. Add or update permission components for new controller actions.
8. Check behaviors attached through permission config, especially condition
   removers.
9. Avoid exposing passwords, reset tokens, hashes, or role internals in
   `userExpose`.
10. Test failed login, forbidden login, successful login, identity response,
    and one protected action after login.
