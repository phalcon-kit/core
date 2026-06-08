# PhalconKit Security And Random

Use this reference when a task touches low-level security services, UUIDs,
hashing, JWT config, crypt config, cookies, or response security headers. For
login flows and ACL behavior, also read `identity-and-security.md`.

## Phalcon Baseline

Native Phalcon references:

- Security and password hashing: https://docs.phalcon.io/5.14/encryption-security/
- UUID factory: https://docs.phalcon.io/5.14/encryption-security/
- JWT: https://docs.phalcon.io/5.14/encryption-security-jwt/
- Cryptography: https://docs.phalcon.io/5.14/encryption-crypt/
- Cookies and HTTP APIs: https://docs.phalcon.io/5.14/api/phalcon_http/
- Response headers: https://docs.phalcon.io/5.14/response/

PhalconKit extends native Phalcon security by installing a custom random
generator, keeping a `uuidv7()` convenience wrapper, and reading app config
defaults. Phalcon 5.14 also exposes native UUID v1-v7 generation through
`Phalcon\Encryption\Security\Uuid`; use native docs for base hashing, CSRF,
JWT, crypt, cookie, UUID, and response semantics.

## Security Service

The `security` DI service is provided by
`PhalconKit\Provider\Security\ServiceProvider` and returns
`PhalconKit\Encryption\Security`.

The core security class extends Phalcon's security service and replaces the
random generator with `PhalconKit\Encryption\Security\Random`.

```php
$uuid = $this->security->getRandom()->uuidv7();
```

Use the injected service from controllers, tasks, providers, and models instead
of creating a new random generator manually.

## UUIDv7

`PhalconKit\Encryption\Security\Random` adds:

- `uuidv4()`: alias around Phalcon's UUID generator.
- `uuidv7()`: timestamp-ordered UUID string using Unix milliseconds plus random
  bytes.

On Phalcon 5.14+, native UUID v7 is also available:

```php
$uuid = (string) (new \Phalcon\Encryption\Security\Uuid())->v7();
```

Prefer UUIDv7 for app-generated model UUIDs because the default model UUID
behavior already uses it.

```php
$security = $this->di->get('security');
$uuid = $security->getRandom()->uuidv7();
```

## Model UUID Behavior

`PhalconKit\Mvc\Model\initialize()` calls `initializeUuid()` by default. The
UUID trait reads model options from the model's option manager:

```php
$this->getOptionsManager()->set('uuid', [
    'field' => 'uuid',
    'native' => false,
    'binary' => false,
]);
```

Options:

- `field`: model property to fill. Default is `uuid`.
- `native`: use database `UUID()` or `UUID_TO_BIN(UUID())` instead of
  app-generated UUIDv7.
- `binary`: store a binary UUID value.

Default behavior:

```php
// beforeValidationOnCreate
$model->uuid ??= $this->security->getRandom()->uuidv7();
```

Rules:

- Keep UUID behavior in model options or the app base model.
- Use `native` only when the target database supports the expected function.
- Use `binary` only when the schema column is binary and the app consistently
  handles binary UUID values.
- Validate UUID uniqueness through generated or concrete model validation.

## Password Hashing

`PhalconKit\Encryption\Security::hash()` reads Argon2 defaults from config when
the default hash is Argon2i or Argon2id:

```php
'security' => [
    'hash' => \Phalcon\Encryption\Security::CRYPT_ARGON2ID,
    'workFactor' => 12,
    'salt' => Env::get('SECURITY_SALT', ''),
    'argon2' => [
        'memoryCost' => PASSWORD_ARGON2_DEFAULT_MEMORY_COST,
        'timeCost' => PASSWORD_ARGON2_DEFAULT_TIME_COST,
        'threads' => PASSWORD_ARGON2_DEFAULT_THREADS,
    ],
],
```

Model code can use the `Hash` trait helpers:

```php
public function afterValidation(): void
{
    if ($this->hasChanged('password') && !empty($this->getPassword())) {
        $this->setPassword($this->hash($this->getPassword()));
    }
}
```

`hash()` prepends `security.salt` and uses `security.workFactor` as the default
cost. `checkHash()` applies the same salt before checking the stored hash.

Rules:

- Hash passwords in model hooks or identity/domain services, not in controllers.
- Never log plaintext passwords, password hashes, JWTs, or security salts.
- For custom auth endpoints, validate credentials first, then call identity
  session methods only after authorization succeeds.

## JWT And Identity Boundary

JWT config lives under `security.jwt`, while login/session behavior lives in the
identity service:

```php
'security' => [
    'jwt' => [
        'signer' => \Phalcon\Encryption\Security\JWT\Signer\Hmac::class,
        'algo' => 'sha512',
        'expiration' => $timestamp,
        'notBefore' => $timestamp,
        'issuedAt' => $timestamp,
        'issuer' => '...',
        'audience' => '...',
        'id' => '...',
        'subject' => '...',
        'passphrase' => '...',
    ],
],
```

Do not bypass the identity manager to issue application login state. The
identity manager owns:

- JWT/session identity keys.
- User and impersonation state.
- Role and ACL role resolution.
- `setSessionIdentity()` and `removeSessionIdentity()`.

## Crypt And Cookies

Core config also defines `crypt` and `cookies` defaults. Use these through the
registered DI services:

- `crypt`: encryption/decryption service.
- `cookies`: encrypted or signed cookie service depending on config.

Keep real keys in environment variables or secret storage. Do not copy default
example keys into production applications.

## Response Security Headers

Default security and CORS headers live under `response.headers` and
`response.corsHeaders`.

Use config overrides instead of setting global headers in controllers:

```php
'response' => [
    'headers' => [
        'Strict-Transport-Security' => 'max-age=63072000; includeSubDomains; preload',
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'Deny',
    ],
],
```

For CORS and preflight behavior, read `routing-and-dispatch.md`.
