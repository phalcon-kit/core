# PhalconKit Integration Providers

Use this reference when adding, configuring, replacing, or debugging built-in
integration providers in a PhalconKit application. For the provider lifecycle
and override pattern, also read `providers.md`.

## Phalcon Baseline

Native Phalcon references:

- Dependency injection: https://docs.phalcon.io/5.15/di/
- Storage: https://docs.phalcon.io/5.15/storage/
- Cache: https://docs.phalcon.io/5.15/cache/
- Logger: https://docs.phalcon.io/5.15/logger/
- Sessions: https://docs.phalcon.io/5.15/session/

PhalconKit integration providers register app-ready services in the native DI
container. Use native docs for storage, cache, logger, session, and service
resolution semantics; use this file for PhalconKit provider names and app
integration boundaries.

## Provider Catalog

Core integration providers register stable DI service names and read matching
config sections. Many related packages are listed in Composer `suggest` and
may need to be required by the application when used outside core development.

| Area | Service | Config | Typical use |
| --- | --- | --- | --- |
| AWS SDK | `aws` | `aws` | tasks, providers, file/storage services |
| File storage | `fileSystem` | `fileSystem` | upload controllers, import/export tasks, domain services |
| OCR | `ocr` | app-specific OCR options | import tasks, document processing |
| reCAPTCHA | `reCaptcha` | `reCaptcha` | public forms, auth and registration controllers |
| Redis | `redis` | `redis` | cache coordination, pub/sub, queue-like app flows |
| Swoole | `swoole` | `swoole` | WebSocket module and long-running servers |
| Mailer | `mailer` | `mailer` | email tasks, auth mail, notifications |
| IMAP | `imap` | `imap` | inbound mail imports |
| ClamAV | `clamav` | `clamav` | upload and file scanning |
| OpenAI | `openAi` | `openai` | app domain services that call OpenAI APIs |
| OAuth2 generic | `oauth2Client` | `oauth2.client` | custom OAuth2 provider |
| OAuth2 Facebook | `oauth2Facebook` | `oauth2.facebook` | Facebook login/linking |
| OAuth2 Google | `oauth2Google` | `oauth2.google` | Google login/linking |

Database, cache, metadata, annotations, logger, locale, request/response, and
security providers are also core services, but those are covered by their
specific references.

## Configuration Rules

Keep integration config in app config, fed by environment variables:

```php
'redis' => [
    'host' => Env::get('REDIS_HOST', '127.0.0.1'),
    'port' => Env::get('REDIS_PORT', 6379),
    'auth' => Env::get('REDIS_AUTH', null),
    'database' => Env::get('REDIS_DB', 0),
],
```

Rules:

- Keep secrets in `.env`, secret storage, or deployment config.
- Do not paste service-account JSON, API keys, OAuth secrets, SMTP passwords,
  SFTP keys, or Redis passwords into skills, prompts, tests, or docs.
- Add app-owned config sections for app-owned providers, such as `firebase`.
- Preserve service names when overriding a core provider.
- Verify optional PHP extensions and Composer packages before using an
  integration in runtime code.

## Where To Use Integrations

Use integrations close to the workflow that owns them:

- Controllers should validate request shape and delegate business work.
- CLI tasks can coordinate imports, exports, mail, OCR, and long-running jobs.
- WebSocket tasks can use Redis/Swoole for pub/sub and channel broadcasts.
- Domain services or providers should wrap external API calls that are reused.
- Models can publish lightweight events when the behavior is already local to
  persistence, but avoid putting heavy external API calls in model hooks.

## File Storage

The `fileSystem` service is backed by Flysystem-style adapters. Core config has
drivers for local, FTP, SFTP, memory, read-only, S3, Google Cloud Storage,
Azure Blob Storage, WebDAV, and zip archive.

Rules:

- Use local or memory storage in tests.
- Use read-only storage for consumers that must not write.
- Keep upload validation, MIME checks, and virus scanning separate from the
  storage adapter.
- Store app-visible file metadata in models; do not infer all metadata from
  storage paths.

## Redis And Swoole

Redis and Swoole are common in WebSocket apps:

```php
$this->redis->publish('websocket', json_encode([
    'type' => 'vote',
    'id' => $vote->getId(),
]));
```

Rules:

- Use Redis pub/sub for cross-worker notifications.
- Keep WebSocket channel payloads compact.
- Log channel type and ids, not full private payloads.
- Verify the app has `ext-redis`, `ext-swoole`, and the right container/runtime
  image before adding WebSocket behavior.

## Mailer And IMAP

Mailer config supports `sendmail` and `smtp`. The selected driver is normalized
case-insensitively and invalid driver/options shapes fail before the mailer is
created. SMTP encryption accepts `ssl`, `tls`, or an empty string; values are
also normalized case-insensitively. IMAP config contains the mailbox path, login,
password, attachments directory, server encoding, and filename mode.

Rules:

- Keep email rendering in views/templates or dedicated services.
- Avoid sending mail directly from model hooks unless the app already follows
  that pattern.
- For IMAP imports, use CLI tasks and explicit runtime limits rather than web
  request handlers.

## Security Integrations

Use `reCaptcha` for public forms and bot-sensitive auth/register flows. Use
`clamav` for upload scanning where the app accepts untrusted files.

Rules:

- Treat a reCAPTCHA score as one signal, not proof of identity.
- Treat ClamAV failures and timeouts as upload failures unless the app has an
  explicit fallback policy.
- Do not log full uploaded files, scan payloads, or service responses that may
  include private data.

## Provider-Specific References

- OAuth2 runtime: `oauth2.md`
- OpenAI runtime: `openai-runtime.md`
- Logging and profiler integration: `logging-and-observability.md`
- WebSocket/Swoole workflows: `cli-and-websocket.md`
