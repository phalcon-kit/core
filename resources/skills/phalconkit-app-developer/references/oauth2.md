# PhalconKit OAuth2 Runtime

Use this reference when adding or debugging OAuth2 provider config, OAuth2
routes, callback handling, account linking, or identity login through OAuth2.
For general identity/session behavior, also read `identity-and-security.md`.

## Phalcon Baseline

Native Phalcon references:

- Sessions: https://docs.phalcon.io/5.15/session/
- Request: https://docs.phalcon.io/5.15/request/
- Response redirects: https://docs.phalcon.io/5.15/response/
- Dependency injection: https://docs.phalcon.io/5.15/di/

Phalcon does not provide OAuth2 as a native business component here; PhalconKit
wires OAuth2 providers through DI and uses native sessions, requests, and
responses for state validation, callbacks, and redirects.

## Services And Config

Core OAuth2 provider services:

- `oauth2Client`: generic `League\OAuth2\Client\Provider\GenericProvider`
  from `oauth2.client`.
- `oauth2Facebook`: Facebook provider from `oauth2.facebook`.
- `oauth2Google`: Google provider from `oauth2.google`.

Root config sections:

```php
'oauth2' => [
    'client' => [
        'clientId' => Env::get('OAUTH2_CLIENT_ID'),
        'clientSecret' => Env::get('OAUTH2_CLIENT_SECRET'),
        'redirectUri' => Env::get('OAUTH2_CLIENT_REDIRECT_URI', '/oauth2/client/redirect'),
        'urlAuthorize' => Env::get('OAUTH2_CLIENT_URL_AUTHORIZE', '/oauth2/client/authorize'),
        'urlAccessToken' => Env::get('OAUTH2_CLIENT_URL_ACCESS_TOKEN', '/oauth2/client/token'),
        'urlResourceOwnerDetails' => Env::get('OAUTH2_CLIENT_URL_RESOURCE', '/oauth2/client/resource'),
    ],
    'facebook' => [
        'clientId' => Env::get('OAUTH2_FACEBOOK_CLIENT_ID'),
        'clientSecret' => Env::get('OAUTH2_FACEBOOK_CLIENT_SECRET'),
        'redirectUri' => Env::get('OAUTH2_FACEBOOK_CLIENT_REDIRECT_URI', '/oauth2/facebook/callback'),
    ],
    'google' => [
        'clientId' => Env::get('OAUTH2_GOOGLE_CLIENT_ID'),
        'clientSecret' => Env::get('OAUTH2_GOOGLE_CLIENT_SECRET'),
        'redirectUri' => Env::get('OAUTH2_GOOGLE_CLIENT_REDIRECT_URI', '/oauth2/google/callback'),
        'hostedDomain' => Env::get('OAUTH2_GOOGLE_CLIENT_HOSTED_DOMAIN', null),
    ],
],
```

Keep client secrets out of source and docs.

## OAuth2 Module Controllers

The core OAuth2 module provides provider-specific controllers such as:

- `ClientController`
- `FacebookController`
- `GoogleController`
- `GithubController`
- `InstagramController`
- `LinkedinController`

Provider controllers set:

- `providerName`
- `sessionKey`
- `$oauth2Provider` service through the base controller/module wiring

The abstract controller provides:

- `authorizationUrlAction()`: redirects to the provider authorization URL and
  stores state in session.
- `validateState()`: checks callback state against the session key.
- `getAccessToken()`: exchanges callback code for an access token.
- `refreshToken()`: refreshes a token.
- `getResourceOwner()`: fetches the provider resource owner.

## Callback And State Rules

OAuth2 callbacks must validate state before trusting a code:

```php
if (!$this->validateState()) {
    return $this->setRestErrorResponse(401, 'Invalid OAuth2 state');
}

$token = $this->getAccessToken();
$owner = $this->getResourceOwner($token);
```

Rules:

- Use one session state key per provider.
- Verify redirect URIs exactly match the provider console configuration.
- Use HTTPS redirect URIs outside local development.
- For Google hosted-domain restrictions, configure and verify the hosted domain
  before creating or linking an account.
- Do not log OAuth codes, access tokens, refresh tokens, or provider secrets.

## Identity Linking

`PhalconKit\Identity\Traits\Oauth2::oauth2()` stores or updates an OAuth2 row
by provider and provider UUID. It stores access token, refresh token, JSON
metadata, and email, then links the OAuth2 row to a user.

Important behavior:

- If an OAuth2 row has no `userId` and the current session has a user, it links
  the OAuth2 row to that current user.
- `userId` is required before login succeeds.
- On success, it calls `setSessionIdentity(['userId' => $user->getId()])`.
- Deleted users are rejected.

Rules:

- Put app-specific account lookup and creation policy around the core trait.
- Do not silently create privileged users from provider metadata.
- Decide whether first-time OAuth users must be invited, manually linked, or
  created with a low-privilege role.
- Treat provider email as untrusted until the provider confirms it and the app
  policy accepts it.

## App-Owned OAuth Controllers

Use app controllers when the app needs custom account rules:

```php
class GoogleController extends \PhalconKit\Modules\Oauth2\Controllers\GoogleController
{
    public function callbackAction(): array
    {
        if (!$this->validateState()) {
            $this->setStatusCode(401);
            return ['loggedIn' => false];
        }

        $token = $this->getAccessToken();
        $owner = $this->getResourceOwner($token);
        $meta = $owner->toArray();

        return $this->identity->oauth2(
            $this->providerName,
            (string) ($meta['sub'] ?? $owner->getId()),
            $this->getToken($token),
            $this->getRefreshToken($token),
            $meta
        );
    }
}
```

Keep the callback thin. Put non-trivial invitation, domain, role, or account
creation policy into an identity manager or domain service.

## Tests

For OAuth2 work, test:

- Provider service can be retrieved from DI with expected config.
- Authorization redirects set a state value in session.
- Callback rejects missing, wrong, or expired state.
- Existing linked OAuth2 rows log in the mapped user.
- First-time OAuth2 rows follow the app account-linking policy.
- Deleted users and forbidden domains are rejected.
