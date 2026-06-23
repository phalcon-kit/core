# Official Phalcon Baseline

Use this reference when a PhalconKit pattern extends native Phalcon behavior.
PhalconKit is a convention layer on top of Phalcon, so agents should understand
the native component before changing the PhalconKit extension point.

Prefer the documentation version that matches the application's installed
Phalcon version. These links point at the current Phalcon 5 docs used while
building this skill reference.

## Core Runtime

- MVC overview: https://docs.phalcon.io/5.16/mvc/
- Dependency injection: https://docs.phalcon.io/5.16/di/
- Loader/autoloading: https://docs.phalcon.io/5.16/autoload/
- Config service: https://docs.phalcon.io/5.16/config/
- CLI applications: https://docs.phalcon.io/5.16/cli/
- Namespaces: https://docs.phalcon.io/5.16/namespaces/

## HTTP And Dispatch

- Controllers: https://docs.phalcon.io/5.16/controllers/
- Routing: https://docs.phalcon.io/5.16/routing/
- Dispatcher API: https://docs.phalcon.io/5.16/api/phalcon_mvc/#mvcdispatcher
- Events manager: https://docs.phalcon.io/5.16/events/
- Request: https://docs.phalcon.io/5.16/request/
- Response: https://docs.phalcon.io/5.16/response/
- Cookies and HTTP APIs: https://docs.phalcon.io/5.16/api/phalcon_http/
- Sessions: https://docs.phalcon.io/5.16/session/

## Security And ACL

- ACL: https://docs.phalcon.io/5.16/acl/
- Security and password hashing: https://docs.phalcon.io/5.16/encryption-security/
- JWT: https://docs.phalcon.io/5.16/encryption-security-jwt/
- Cryptography: https://docs.phalcon.io/5.16/encryption-crypt/

## Filters And Validation

- Filters and sanitizers: https://docs.phalcon.io/5.16/filter-filter/
- Validation component: https://docs.phalcon.io/5.16/filter-validation/
- Model validation: https://docs.phalcon.io/5.16/db-models-validation/

Native Phalcon filter names in the current docs:

```text
absint, alnum, alpha, bool, email, float, int, ip, lower, lowerfirst,
regex, remove, replace, special, specialfull, string, stringlegacy,
striptags, trim, upper, upperfirst, upperwords, url
```

Native Phalcon validation classes in the current docs:

```text
Alnum, Alpha, Between, Callback, Confirmation, CreditCard, Date, Digit,
Email, ExclusionIn, File, Identical, InclusionIn, Ip, Numericality,
PresenceOf, Regex, StringLength, Uniqueness, Url
```

## Database And Models

- Models: https://docs.phalcon.io/5.16/db-models/
- Relationships: https://docs.phalcon.io/5.16/db-models-relationships/
- Behaviors: https://docs.phalcon.io/5.16/db-models-behaviors/
- PHQL: https://docs.phalcon.io/5.16/db-phql/
- Model events: https://docs.phalcon.io/5.16/db-models-events/
- Metadata: https://docs.phalcon.io/5.16/db-models-metadata/
- Transactions: https://docs.phalcon.io/5.16/db-models-transactions/
- Migrations: https://docs.phalcon.io/5.16/db-migrations/
- Devtools: https://docs.phalcon.io/5.16/devtools/

## Services And Frontend

- Logger: https://docs.phalcon.io/5.16/logger/
- Cache: https://docs.phalcon.io/5.16/cache/
- Storage: https://docs.phalcon.io/5.16/storage/
- Escaper: https://docs.phalcon.io/5.16/escaper/
- View: https://docs.phalcon.io/5.16/views/
- Volt: https://docs.phalcon.io/5.16/volt/
- URL: https://docs.phalcon.io/5.16/url/
- Helper: https://docs.phalcon.io/5.16/helper/
- Collection: https://docs.phalcon.io/5.16/collection/

## Testing And Environment

- Webserver setup: https://docs.phalcon.io/5.16/webserver-setup/
- Docker environment: https://docs.phalcon.io/5.16/environments-docker/
- Unit testing: https://docs.phalcon.io/5.16/unit-testing/
- Testing environment: https://docs.phalcon.io/5.16/testing-environment/
- Reproducible tests: https://docs.phalcon.io/5.16/reproducible-tests/

## Use Rules

- Treat this as a navigation map, not as a replacement for the official docs.
- If a PhalconKit reference and native Phalcon docs disagree, inspect the core
  source and the installed Phalcon version before changing app code.
- Do not copy large official documentation tables into PhalconKit skills. Keep
  concise quick lists only where they prevent common mistakes.
