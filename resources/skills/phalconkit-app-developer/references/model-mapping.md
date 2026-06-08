# PhalconKit Model Mapping

Use this reference when an app replaces core PhalconKit models with app models,
adds model aliases to config, writes providers that depend on core models, or
tests model replacement behavior.

## Phalcon Baseline

Native Phalcon references:

- Models: https://docs.phalcon.io/5.14/db-models/
- Column mapping: https://docs.phalcon.io/5.14/db-models/#column-mapping
- Dependency injection: https://docs.phalcon.io/5.14/di/

PhalconKit model mapping complements native Phalcon model classes and column
maps. Use native docs for ORM model behavior and this file for replacing core
PhalconKit model contracts with app-owned classes.

## Purpose

PhalconKit core services refer to core model contracts such as
`PhalconKit\Models\User`, `Audit`, `Session`, `Role`, and `File`. Real apps
often need app-owned models for the same tables so they can add relationships,
validation, and business logic.

The model map connects core model classes to app model classes:

```php
'models' => [
    \PhalconKit\Models\User::class => \App\Models\User::class,
    \PhalconKit\Models\Session::class => \App\Models\Session::class,
    \PhalconKit\Models\Audit::class => \App\Models\Audit::class,
],
```

## Models Service

The `models` DI service is provided by
`PhalconKit\Provider\Models\ServiceProvider` and returns
`PhalconKit\Support\Models`.

It uses `PhalconKit\Support\ModelsMap` to resolve mapped classes and exposes
typed getters:

```php
$models = $this->di->get('models');

$userClass = $models->getUserClass();
$sessionClass = $models->getSessionClass();

$user = $models->getUser();
```

Rules:

- Use the `models` service when framework or app infrastructure needs the
  configured class for a core model.
- Do not hard-code `PhalconKit\Models\User::class` inside app providers when
  the app can map it to `App\Models\User::class`.
- For new app domain models that are not replacements for core models, use
  normal app namespaces and do not add them to the core model map unless a
  core service must resolve them.

## Interface Contract

When replacing a core model, the app model should implement the app interface
and the matching PhalconKit core model interface.

```php
namespace App\Models;

use App\Models\Abstracts\UserAbstract;
use App\Models\Interfaces\UserInterface;

class User extends UserAbstract implements UserInterface, \PhalconKit\Models\Interfaces\UserInterface
{
}
```

This matters because core providers and behaviors may assert against the core
interface rather than only the concrete class.

## Where Mapping Is Used

Mapping affects framework code that asks the `models` service for core model
classes, including:

- Identity and auth lookup.
- Session identity implementations.
- ACL and role-related models.
- Blameable/audit behavior.
- Cache behavior exclusions for session/audit models.
- Providers or tasks that access core data models.

Example from app config:

```php
'models' => [
    \PhalconKit\Models\Audit::class => \App\Models\Audit::class,
    \PhalconKit\Models\AuditDetail::class => \App\Models\AuditDetail::class,
    \PhalconKit\Models\User::class => \App\Models\User::class,
    \PhalconKit\Models\Role::class => \App\Models\Role::class,
],
```

## Provider Usage

Use model mapping inside providers:

```php
$models = $di->get('models');
$userClass = $models->getUserClass();

$user = $userClass::findFirstByEmail($email);
```

For a custom identity provider, keep `$serviceName = 'identity'` but let the
manager use mapped app models for session/user lookup.

## Tests

Add focused tests when changing mappings:

- Config contains the expected core-to-app class pair.
- `$this->di->get('models')->getUserClass()` returns the app class.
- `$this->di->get('models')->getUser()` returns an instance implementing the
  core user interface.
- Identity, ACL, and audit behavior still use the mapped class.

For docs-only mapping examples, run `git diff --check` and path searches. For
runtime mapping changes, run the app's PHPUnit suite or the narrow tests around
config/providers/models.
