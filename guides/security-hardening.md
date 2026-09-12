# Security Hardening Upgrade Notes

These changes are in the Core **3.10.7 unreleased working tree**. Core 3.10.6
already enforces JWT validator errors, but does not include the additional
fixes below. Publish and install the fixed package before claiming these
protections in a consuming application.

## Signing Keys

Set `SECURITY_JWT_PASSPHRASE` to a private, randomly generated key before using
JWTs. For the default HMAC signer, generate 64 random bytes, for example with
`php -r 'echo bin2hex(random_bytes(64)), PHP_EOL;'`, and store the result in an
untracked environment file or deployment secret store. Generate once per
environment, share among its application instances, and retain across restarts.
Never place a real key in source control, logs, tickets, or release notes.

The framework no longer supplies a signing key. Missing/blank keys and the
previously shipped public key fail closed during token operations. Anonymous
bootstrap and requests without credentials do not require token issuance.
Credential validation failures return a generic HTTP 401; signing with invalid
configuration raises the framework ConfigurationException. The public
`validateToken()` contract still returns validation errors for configured keys.

Changing the signing key invalidates all existing access and refresh tokens.
Applications that retained the shared default must rotate it and require a new
login. Review their exposure, particularly if stateless identity was enabled:
that mode trusted the user id in tokens signed with the publicly known key.

## Identity And Authorization

Deleted user records no longer authenticate from an existing JWT or become
impersonation targets. Both session-backed and stateless identity use this
check. Existing password-login handling is retained.

Stateless `setSessionIdentity()` now replaces the old identity payload, as
session-backed storage already does. Only the token bookkeeping key is retained
implicitly. Include custom fields explicitly when replacing identity. Returning
from impersonation clears `asUserId`, so a later account login cannot inherit a
prior administrator's return capability.

Identity mutations clear cached effective/original users and model ACL roles.
Applications overriding `setSessionIdentity()` or `removeSessionIdentity()` for
database storage must call the protected `clearIdentityCache()` helper after
changing storage and before further authorization. Call the parent method when
that matches the custom storage implementation; otherwise call the helper.

User lookup and model authorization restore their recursion guards in `finally`
blocks. A caught exception no longer leaves subsequent model operations exempt
from authorization. Nested guards retain their caller's state.

These changes do not alter access/refresh lifetimes, idle-session policy, or
absolute session duration. Stateless logout still cannot revoke an already
issued token without application-owned revocation; discard old tokens and keep
existing revocation controls. Custom identity overrides must be tested by their
own consumers. Workers must still initialize and clean up per-request state.

## Cross-Origin Requests

Cross-origin access now requires an explicit origin configuration. Set
`RESPONSE_HEADER_ACCESS_CONTROL_ALLOW_ORIGIN` to a comma-separated list of exact
trusted origins, such as `https://frontend.example`, and enable
`RESPONSE_HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS=true` only if those origins
need browser-managed credentials. Same-origin clients need no CORS settings.

Wildcard configuration emits `Access-Control-Allow-Origin: *` and disables
credential grants. It no longer reflects arbitrary origins with credentials.
Explicit origins preserve configured credential support and add `Vary: Origin`,
including on denied-origin responses. Existing application-supplied response
headers remain authoritative and must be reviewed separately. CORS is not a
replacement for CSRF protection on cookie-authenticated state-changing routes.

## REST Field Selectors

Unrestricted client `filters`, `order`, and `group` fields now accept identifiers
and supported relation paths/scopes, rather than query expressions. Invalid
expressions receive HTTP 400 before query compilation. Values remain bound.

For computed ordering, expose a public alias through `setOrderFields()`:

```php
$this->setOrderFields(['scoreRank' => 'ABS([Score].[value])']);
```

Clients send `order[scoreRank]=desc`. Keep expressions in controller-owned
configuration, `setOrder()` / `setGroup()`, or defaults; do not concatenate client
input into them. `appendModelName()` is a trusted expression formatter, not a
sanitizer for arbitrary request input. Field allowlists and row authorization
remain necessary for controlling which data a client may access.

Custom controllers or test doubles that compose query traits separately must
also provide the protected `assertRequestField(string $field): void` contract.
Use the Core `Model` trait's implementation, or enforce equivalent identifier
validation and HTTP 400 rejection in an override. The standard REST controller
already includes it.

## Encryption Keys And Existing Data

The `crypt` service now requires a private key of at least 32 bytes. Set
`CRYPT_KEY` (`APP_CRYPT_KEY` remains a fallback) or `crypt.key`. For a new
environment, generate an independent key, separate from the JWT signing key:

```shell
php -r 'echo "base64:", base64_encode(random_bytes(32)), PHP_EOL;'
```

Store that value in secret storage or an untracked `.env`. The `base64:` prefix
decodes the 32 random bytes before encryption. Unprefixed keys retain their
existing bytes; an existing raw key beginning with `base64:` must instead be
encoded in full using this format to preserve its bytes. The old public key,
missing keys, short keys, and blank keys are rejected before use. AES-256-GCM
defaults to unsigned mode because GCM authenticates the ciphertext itself.
Its associated data defaults to `phalcon-kit`; set `CRYPT_AUTH_DATA` or
`crypt.authData` to retain an existing application value. Associated data is
not a secret, but must match exactly when decrypting.

Do not replace keys or associated data blindly when encrypted data already
exists. No automatic rotation or re-encryption occurs. Back up the data and
old key/cipher/signing/associated-data settings, test decryption offline, then
use a reviewed migration to decrypt with the original native Phalcon `Crypt`
configuration and re-encrypt with a private key. Verify the migrated data
before deployment and retain a rollback plan. The runtime provider deliberately
does not offer a switch to keep using the public key. Existing encrypted
cookies may require reissuing; stored ciphertext requires a data migration.

## Password Reset

New records use `v1:<expiry>:<hash>` in the existing `reset_token` column.
Core's 255-character column accommodates the default hashes; custom hashes
must fit their application's column. Legacy undated records are rejected:
users with pending reset links must request new ones. The default lifetime is
30 minutes, configurable with `IDENTITY_RESET_PASSWORD_LIFETIME` or
`identity.resetPassword.lifetime` (1–86400 seconds).

Token creation and verification both use the user's configured hash/salt
policy. Redemption checks expiry, then conditionally clears the exact stored
record inside the user's write transaction, saves the new hashed password
through model hooks, and commits. It checks expiry again after obtaining the
claim. A stale concurrent model cannot reuse an already consumed record.
Rejected saves and failed commits roll back the claim and password change.

The default requires transactional tables and model saves using that same
write connection. It rejects caller-owned outer transactions. Custom stores
must override `persistPasswordReset()` with equivalent atomic comparison,
expiry checks, password persistence, rollback, and lost-race rejection.
Retain dynamic model updates so stale reset requests do not overwrite an
unchanged password. Test application model hooks against the transaction.

Applications must override `sendPasswordResetNotification(UserInterface $user,
string $token, int $expiresAt): void` to deliver the persisted credential by
their approved email/queue channel. The default sends nothing. Build reset
links from a trusted configured application URL, never an untrusted request
Host header. Do not log raw tokens or include them in API responses. Unknown
or deleted accounts receive the same empty request response as active users.
Apply rate limiting and abuse controls to the endpoint and delivery channel.

Core hashes the new password in `setPasswordAfterReset()`. Applications whose
model setter/save hook already hashes plaintext must override this helper
to avoid double hashing, and must preserve existing stored hashes when
restoring a failed save. Apply password policy in model validation or the
application override. Existing sessions are not revoked automatically;
revocation and post-reset notifications remain application policy.

## OAuth2 Callbacks

Authorization now stores a provider-bound record with an expiry and any
configured PKCE verifier in the session. `OAUTH2_STATE_LIFETIME` /
`oauth2.stateLifetime` defaults to 10 minutes (allowed range 1–86400 seconds).
Pending logins created with the old string-only state must restart.

Callbacks may continue to call `validateState()` once before
`getAccessToken()`. The exchange method also validates request state itself
when needed. Missing, malformed, expired, wrong-provider, or mismatched state
fails before any provider exchange. Comparison uses the original value;
state is not sanitized into a match. Successful validation consumes stored
state and authorizes only one exchange, including if that exchange fails.
Start a fresh authorization attempt after a failure.

Keep one state key per provider and use a session handler that serializes
callback requests. Custom concurrent backends must provide equivalent atomic
consumption; the controller's get/remove operations are not a distributed
lock. PKCE is preserved when configured on the League provider; this change
does not enable it for every provider or change account-linking policy.

## Release And Consumer Validation

1. Release the fixed Core package after signed commit/tag and CI verification.
2. Raise the companion App Core requirement, update its committed lockfile,
   run App QA, and verify a public Composer create-project installation.
3. In existing applications, configure private keys and explicit CORS origins,
   review encryption migration needs, reset hooks, custom identity persistence,
   OAuth callbacks, and computed query selectors, then update Core through
   Composer and deploy the reviewed lockfile.
4. Validate valid login/refresh/logout/impersonation, rejected credentials,
   deleted-account access, reset expiry/replay/rollback, encryption round trips,
   OAuth expiry/replay/PKCE, CORS, and custom authorization before deployment.

The regressions use the actual Core checkout with synthetic keys/users and
isolated storage. They do not require live application databases. The opt-in
`PasswordResetDatabaseTest` uses `PHALCONKIT_RESET_TEST_SOCKET` to create and
remove a random synthetic schema on a disposable MariaDB/MySQL instance.
Never point it at an application database server. Application-specific identity
overrides and other database engines remain consumer checks.
