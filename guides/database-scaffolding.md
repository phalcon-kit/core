# Generate Models From Your Database

PhalconKit is database-first. You design the database, then let the scaffolder
write the repetitive model layer.

The usual flow is:

1. Design or update the database schema.
2. Run migrations.
3. Run the scaffolder.
4. Keep generated structure in abstract models and interfaces.
5. Put business logic in concrete application models.

The scaffolder maps what can be inferred from the real database:

- model classes
- abstract model classes
- abstract interfaces
- typed properties, getters, and setters
- comments and column metadata
- column maps
- relationships and aliases
- relationship helper annotations
- validations
- enum classes
- model tests

That gives you typed model structure quickly while keeping business logic in
concrete app models.

Official Phalcon references:

- Models: https://docs.phalcon.io/5.14/db-models/
- Relationships: https://docs.phalcon.io/5.14/db-models-relationships/
- Model validation: https://docs.phalcon.io/5.14/db-models-validation/
- Migrations: https://docs.phalcon.io/5.14/db-migrations/
- DevTools: https://docs.phalcon.io/5.14/devtools/

## 1. Run Migrations

Applications commonly use Phalcon DevTools migrations:

```shell
./vendor/bin/phalcon migration run \
  --directory=./ \
  --migrations=./resources/migrations \
  --no-auto-increment \
  --force \
  --verbose \
  --log-in-db
```

Adjust the paths for the application skeleton in use. For team projects, wrap
migration commands in `bin/` scripts so every developer uses the same config
file, migration directory, and flags.

## 2. Run The Scaffolder

Generate missing model files:

```shell
./vendor/bin/phalcon-kit cli scaffold run \
  --src-dir=app/ \
  --namespace=App \
  --models-extend=\\App\\Models\\AbstractModel
```

Regenerate generated layers without overwriting concrete models:

```shell
./vendor/bin/phalcon-kit cli scaffold run \
  --src-dir=app/ \
  --namespace=App \
  --models-extend=\\App\\Models\\AbstractModel \
  --force \
  --no-models
```

Use full `--force` only when overwriting concrete model shells is intentional.

## 3. Add Business Logic To Concrete Models

Generated abstract classes should be treated as schema output. Concrete models
extend them and contain the application-specific methods, custom behaviors, and
business rules.

```php
<?php

namespace App\Models;

final class Project extends Abstracts\ProjectAbstract
{
    public function isActive(): bool
    {
        return !$this->isDeleted() && $this->getStatus() === 'active';
    }
}
```

Typical generated ownership:

- `Models/Abstracts/*Abstract.php`: generated columns, comments, accessors,
  column map, relationships, and validations.
- `Models/Abstracts/Interfaces/*AbstractInterface.php`: generated interface for
  generated methods.
- `Models/Interfaces/*Interface.php`: app-facing model contract.
- `Models/Enums/`: generated enum classes.
- `Models/*.php`: app-owned concrete behavior.

## 4. Use Generated Relationships

Generated relationships are used by:

- eager loading
- REST save payloads
- relation assignment
- nested validation messages
- soft-delete-aware relation updates
- many-to-many relation synchronization

When a schema changes, regenerate the abstracts/interfaces and review concrete
models for any new business logic that should be added.

## What The Scaffolder Guesses

The scaffolder tries to infer safe conventions:

- Table and column names become camelCase model properties.
- Column maps preserve database names while app code uses camelCase.
- `_id` columns are candidates for `belongsTo` aliases such as `UserEntity`.
- Link/node tables are candidates for many-to-many list aliases. Short target
  aliases such as `RoleList` are generated only for canonical junction tables
  named exactly like `user_role` or `role_user`; contextual intermediate tables
  keep the intermediate model in the alias to avoid collisions.
- Unique indexes become uniqueness validations.
- DB enum columns can become PHP enum classes and inclusion validations.
- Date, datetime, JSON, boolean, unsigned, numeric, string, and length metadata
  can become generated validation helpers.

These rules depend on database naming. If a relationship is too app-specific to
infer safely, override it in the concrete model.

## 5. Review Connected API Code

After a schema/scaffold pass:

1. Review generated diffs.
2. Update concrete model business logic where needed.
3. Update REST save/filter/expose/transformer rules.
4. Update permission conditions when new ownership fields are added.
5. Run focused tests and then `composer qa`.
