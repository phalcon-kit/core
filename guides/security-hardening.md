# Application Security

Configure security as part of the application's normal setup. These controls
complement the concrete [authentication](authentication.md) and
[permission](identity-and-permissions.md) recipes.

## Secrets And Keys

Generate a private JWT key using the [tested key command](authentication.md#configure-a-signing-key).
Set `SECURITY_JWT_PASSPHRASE` once per environment and share it only among that
environment's instances. Missing/blank signing keys fail token operations.
Phalcon's JWT builder also checks character classes; hex-only output does not
satisfy its passphrase check.

Use a separate `CRYPT_KEY` for application encryption. Store it in the deployment
secret store or untracked environment. Preserve keys needed to decrypt stored
data; changing an encryption key requires a data/key-rotation plan. Never reuse
a published example value as a secret.

The User model's `hash()`/`checkHash()` pair includes the configured
`security.salt`. Keep that configuration stable and hash passwords explicitly
in an application-owned account workflow. Do not save plaintext passwords,
return password hashes, or accept role assignment from public registration input.

## Public Files And Diagnostics

Serve only `public/`. Keep `.env`, Composer files, source, logs, caches, SQL
backups, and storage outside the document root. Keep `APP_DEBUG=false` in shared
and production environments. Error pages and SQL diagnostics can contain request
values; logs need access control and an application redaction/retention policy.

## Browser Sessions

Use HTTPS, secure/HTTP-only cookies, and an appropriate SameSite policy. The
loopback HTTP tutorial temporarily uses `SESSION_COOKIE_SECURE=0`; deployment
should retain the secure default. Default identity renews session IDs when
establishing authenticated identity and on authenticated refresh.

For cookie-authenticated mutations, add CSRF protection. CORS and an HTTP method
guard are not substitutes. Check concurrent sessions, refresh races, and logout
using the persistence mode your application actually deploys.

## Cross-Origin Requests

Same-origin clients need no CORS configuration. For a separate frontend:

```ini
RESPONSE_HEADER_ACCESS_CONTROL_ALLOW_ORIGIN=https://frontend.example.test
RESPONSE_HEADER_ACCESS_CONTROL_ALLOW_CREDENTIALS=true
```

Use exact trusted origins (comma-separated for several). Enable credentials only
when browser-managed credentials are needed. Allow the configured authorization
header and the HTTP methods your endpoints accept. With default stateful identity,
the frontend must use `credentials: 'include'` and keep both token and cookie.

Wildcard origins emit `*` without credential grants. Explicit origins add
`Vary: Origin`. Test allowed and denied origins and preflight OPTIONS through the
actual reverse proxy, including custom response headers supplied by the app.

## API Boundaries

Use closed expose lists (`[false, ...]`), explicit save/filter/order lists, limited
relationship graphs, bounded pagination, and action/model permissions. Scope
counts, distinct facets, exports, and child queries to the same authorized data
as ordinary reads. Client field identifiers are validated, but application SQL
expressions still need bound values and trusted configuration.

Default relationship assignment is permissive. For direct child records enable
`model.relationship.enforceDirectOwnership` and decide whether unowned children
may be adopted (this includes new children before their foreign keys are set).
Prefer a per-model policy; see [nested writes](rest-relationships.md#write-children). Validate shared belongs-to/many-to-many IDs separately. Never
make tenant, owner, administrative, or audit fields writable simply because the
scaffolder generated accessors for them.

## Application Workflows

| Workflow | Application controls |
| --- | --- |
| Login/reset | Rate limits, generic errors, token lifetime, hashing, delivery, single-use reset policy |
| Upload/download | Size/type validation, private storage, ownership checks, authorized download routes |
| Email | Trusted templates, recipient policy, redaction, retries and deduplication |
| WebSocket | Handshake/message authentication, per-topic authorization, bounded messages, cleanup |
| Export | Field/row policy, download limits, formula-safe spreadsheet output where needed |
| Background command | Explicit input scope, least required DB permissions, logs without credentials |

Use the [scenario checklist](rest-scenarios.md) and
[application testing guide](application-testing.md) to prove allowed and denied
paths. Report framework vulnerabilities through the
[security policy](../SECURITY.md).
