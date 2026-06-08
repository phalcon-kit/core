# PhalconKit App Testing And Validation

Use this reference when choosing validation commands or adding tests for
PhalconKit app changes. Prefer the app's own scripts and fixtures over generic
commands.

## Phalcon Baseline

Native Phalcon references:

- Unit testing: https://docs.phalcon.io/5.14/unit-testing/
- Testing environment: https://docs.phalcon.io/5.14/testing-environment/
- Reproducible tests: https://docs.phalcon.io/5.14/reproducible-tests/
- Debug tools: https://docs.phalcon.io/5.14/debug/

PhalconKit app tests still bootstrap native Phalcon services, DI, models,
controllers, and CLI tasks. Use native docs for generic test setup and this
file for PhalconKit-specific provider, permission, REST, model, and WebSocket
test coverage.

## Command Selection

Start with the app's Composer scripts:

```bash
composer phpunit
composer psalm
composer psalm:taint
composer phpcs
composer skeleton
```

Common core package scripts are:

- `composer phpunit`
- `composer psalm` for normal static analysis
- `composer psalm:taint` for taint analysis when configured
- `composer php-cs-fixer`
- `composer phpcs`
- `composer phpcbf`
- `composer skeleton`
- `composer docs`

Some applications may use PHPStan, Pest, Codeception, or framework-specific
wrappers. Use local scripts when present.

For docs-only changes:

```bash
git diff --check
```

Add targeted searches for changed paths, skill links, env names, service names,
or claims that need support.

## Provider And Config Tests

When adding or overriding providers, test:

- Config contains the expected provider key and actual provider class.
- The DI service name is unchanged when replacing a core provider.
- `$di->get('<service>')` returns the expected object type.
- The provider reads the intended config section.
- Required optional Composer packages and PHP extensions are installed.

Example assertions:

```php
$service = $this->di->get('firebase');
$this->assertInstanceOf(\Kreait\Firebase\Factory::class, $service);
```

## Permission And Identity Tests

For auth, identity, and permission config, test both allowed and denied paths:

- Anonymous/guest access where expected.
- Authenticated user access.
- Role inheritance.
- Impersonation or `asUserId` behavior when used.
- Controller/action permissions.
- Model operation permissions.
- Config-attached behaviors that remove default permission or soft-delete
  conditions.

Use realistic role names from the app, such as `participant`, `admin`, `cli`,
or `ws`.

## REST Controller Smoke Tests

For a new REST resource, cover the smallest useful set:

- `find` or list action with allowed filters.
- `find-with` or eager-loaded list action when relations are exposed.
- `find-first` or detail action.
- `save` or create/update with whitelisted fields.
- Delete or soft-delete if exposed.
- Permission-denied response.
- Filtering on relation fields when joins/dynamic joins are configured.
- Exposer or transformer output shape.

Use narrow fixtures. Avoid building a large end-to-end test when the change is
inside one controller method or one model validation rule.

## Model And Scaffold Tests

After schema or scaffold changes, test:

- Generated abstract class has expected column map and getters/setters.
- Concrete model extends the generated abstract and implements app/core
  interfaces where needed.
- Default relationships resolve expected aliases.
- Generated enum values match the database enum column.
- Default validations catch nullability, unsigned numbers, lengths, uniqueness,
  dates, JSON, color, UUID, and enum domains.
- Concrete model custom validation and event hooks still run.
- Relationship assignment saves single, one-to-many, or many-to-many payloads
  as expected.

For model mapping changes, test the `models` DI service returns mapped app
classes and mapped instances implement the matching core interfaces.

## Eager Loading And Transformers

For eager-loaded APIs, test:

- `findWith()` and `findFirstWith()` include only requested relation aliases.
- Relation-level `QueryBuilder` constraints filter deleted or hidden rows.
- Transformers only include relations that were loaded.
- Exposers or transformers do not trigger accidental lazy-loading loops.
- Custom ordering or two-phase sorting keeps pagination totals correct.

## Long-Running Jobs And WebSockets

For CLI/WebSocket work, test:

- Task permission role, usually `cli` or `ws`.
- Router default task/action.
- Redis publish payload shape.
- Channel subscribe/unsubscribe validation.
- Initial snapshot broadcast.
- Watcher timers stop when no active work remains.
- Failure logs contain ids and context, not secrets or full payloads.

Use integration tests only when the app has a local Redis/Swoole test setup.
Otherwise add narrow unit tests around payload creation and model state changes.

## Documentation Claims

When updating README or AI-facing docs, keep claims supportable:

- Name actual scripts or test folders.
- Say "tested baseline" instead of absolute quality claims.
- Avoid promises that exceed the test evidence in the repository.
- Prefer examples that can be verified from app code.

Docs validation checklist:

- `git diff --check`
- Skill quick validation when changing `resources/skills`
- Search for stale paths or missing references
- Search for unsupported quality claims
