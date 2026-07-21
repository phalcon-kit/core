# PhalconKit Support Helpers

Use this reference when a task needs reusable helpers, app entrypoint loading,
dotenv/runtime setup, debug helpers, slug generation, array cleanup, or
null-aware field policy merging.

## Phalcon Baseline

Native Phalcon references:

- Loader/autoloading: https://docs.phalcon.io/5.17/autoload/
- Helper: https://docs.phalcon.io/5.17/helper/
- Config service: https://docs.phalcon.io/5.17/config/
- Collection: https://docs.phalcon.io/5.17/collection/
- Registry: https://docs.phalcon.io/5.17/registry/

PhalconKit support helpers wrap or extend native loader, helper, config,
collection, and runtime conventions. Use native docs for base component
behavior and this file for PhalconKit helper functions and facade utilities.

## Autoload

PhalconKit includes `PhalconKit\Autoload\Loader`, which extends Phalcon's
loader and disables file checking callbacks in the constructor.

```php
use PhalconKit\Autoload\Loader;

$loader = new Loader();
$loader->setFiles([VENDOR_PATH . 'autoload.php']);
$loader->setNamespaces([APP_NAMESPACE => APP_PATH]);
$loader->register();
```

If an app uses `Phalcon\Autoload\Loader` directly, keep this performance line:

```php
$loader->setFileCheckingCallback(null);
```

Rules:

- Keep shared constants and namespace registration in one `loader.php`.
- Reuse that loader from MVC, CLI, and WebSocket entrypoints.
- Prefer `PhalconKit\Autoload\Loader` for new apps.

## Composer-Loaded Functions

Core Composer autoload loads these helper files:

- `src/Functions/Dump.php`
- `src/Functions/Array.php`
- `src/Functions/Sprintf.php`

Available functions include:

- `dump(...$params)`: CLI prints JSON, web prints Phalcon debug output.
- `dd(...$params)`: dump, set HTTP 500 when possible, then exit.
- `vdd(...$params)`: `var_dump`, set HTTP 500 when possible, then exit.
- `exit_500()`: set HTTP 500 outside CLI and exit with code 1.
- `array_unset_recursive(array &$array, array $keyList, bool $strict = true)`.
- `implode_sprintf()`, `implode_mb_sprintf()`, `sprintfn()`,
  `mb_sprintf()`, and `mb_vsprintf()`.

Use `array_unset_recursive()` to strip client-only relation keys before model
assignment:

```php
public function beforeAssign(ModelInterface &$entity, array &$post): void
{
    array_unset_recursive($post, ['userentity']);
}
```

Rules:

- Remove `dd()` and `vdd()` before committing feature work.
- Do not dump secrets, JWTs, cookies, service account JSON, or raw request
  bodies with credentials.
- Prefer explicit exceptions or validation messages for application flow.

## Helper Facade

`PhalconKit\Support\Helper` is a static facade over the `helper` service. It
wraps Phalcon support helpers and adds PhalconKit helpers.

```php
use PhalconKit\Support\Helper;

$alias = Helper::camelize('vote_submission');
$keys = Helper::flattenKeys($payload);
$slug = Helper::slugify($label);
```

Extra helpers:

- `recursiveMap(array $collection, callable $callback = null)`
- `flattenKeys(array $collection = [], string $delimiter = '.', bool $lowerKey = true)`
- `recursiveStrReplace(array $collection, array $replaces)`
- `slugify(string $string, array $replace = [], string $delimiter = '-')`
- `sanitizeUTF8(string $string, string $invalidUtf8Regex)`
- `removeNonPrintable(string $string, string $nonPrintableRegex = '[[:cntrl:]\r\n]', string $replacement = '')`
- `normalizeLineBreaks(string $string, string $nonPrintableRegex = "\r\n", string $replacement = "\r")`

Use the facade for small transformations. Inject or retrieve the `helper`
service when the class already follows DI patterns.

## Slug

`PhalconKit\Support\Slug::generate()` transliterates to Latin ASCII, applies
optional replacements, and normalizes with a delimiter.

```php
$slug = \PhalconKit\Support\Slug::generate('Resolution 12 A', [], '-');
```

Use `Slug::generate()` when you want the framework slug implementation directly.
Use `Helper::slugify()` when working through the helper facade.

Model slug behavior uses `Slug::generate()` on the configured slug field before
validation.

## Env

`PhalconKit\Support\Env` wraps `vlucas/phpdotenv` and stores loaded values.

```php
Env::load(ROOT_PATH, '.env');
$debug = Env::get('APP_DEBUG', false);
Env::set('APP_VERSION', '1.2.3');
```

Defaults:

- Paths are inferred from `ENV_PATH`, `ROOT_PATH`, `APP_PATH`, then `getcwd()`.
- Names default to `.env`.
- Type defaults to `mutable`.
- Supported types are `mutable`, `immutable`, `unsafe-mutable`, and
  `unsafe-immutable`.

Rules:

- Read config values with `Env::get()` inside config classes.
- Keep runtime code dependent on config, not direct environment reads, unless
  the code is part of bootstrap/config setup.
- Do not commit real `.env` secrets.

## Runtime Utilities

`PhalconKit\Support\Php`:

- `isCli()`: true for CLI and phpdbg.
- `trustForwardedProto()`: turns forwarded HTTPS into `$_SERVER['HTTPS'] = 'on'`.
- `debug()`: toggles error display.
- `set()`: sets timezone, locale, encoding, memory limit, and timeout.

`PhalconKit\Support\Utils`:

- `setUnlimitedRuntime()`: removes time and memory limits for imports/exports.
- Reflection helpers: `getNamespace()`, `getShortName()`, `getName()`,
  `getDirname()`.
- `getMemoryUsage()`: current and peak memory usage.

Use unlimited runtime deliberately in high-volume tasks and long-running import
actions. Avoid enabling it globally for normal API requests.

## Collection Policy

`PhalconKit\Support\CollectionPolicy` handles null-as-unrestricted field
policies:

- `mergeNullable(?Collection $base, Collection $incoming)`
- `intersectNullable(?Collection $base, Collection $incoming)`

Use it when `null` means "all fields allowed" and a collection means "only
these fields allowed".

Rules:

- Do not replace null-aware policy logic with plain `array_merge()` or
  `array_intersect()` unless null is impossible.
- Keep this distinction explicit in controller field and behavior code.
