# Database And Scaffolding

PhalconKit is database-first. The usual flow is:

1. Design or update the database schema.
2. Run migrations.
3. Run the scaffolder.
4. Keep generated structure in abstract models and interfaces.
5. Put business logic in concrete application models.

The scaffolder maps what can be inferred from the database:

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

This keeps generated schema knowledge separate from application-owned business
logic.

## Migrations

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

Adjust the paths for the application skeleton in use.

## Generated Models

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

## Relationship Behavior

Generated relationships are used by:

- eager loading
- REST save payloads
- relation assignment
- nested validation messages
- soft-delete-aware relation updates
- many-to-many relation synchronization

When a schema changes, regenerate the abstracts/interfaces and review concrete
models for any new business logic that should be added.
