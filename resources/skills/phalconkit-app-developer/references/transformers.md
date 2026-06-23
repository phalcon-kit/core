# PhalconKit API Transformers

Use this reference when an app uses League Fractal through PhalconKit's REST
controller support, or when a response shape is better expressed as a
transformer than an exposer array.

## Phalcon Baseline

Native Phalcon references:

- Controllers: https://docs.phalcon.io/5.16/controllers/
- Response: https://docs.phalcon.io/5.16/response/
- Models: https://docs.phalcon.io/5.16/db-models/
- Relationships: https://docs.phalcon.io/5.16/db-models-relationships/

Transformers are not a native Phalcon feature; PhalconKit integrates League
Fractal with native controllers, responses, models, and relationships. Use
native docs for model/response behavior and this file for transformer includes,
serializers, and loaded-relation rules.

## When To Use Transformers

Use exposers for simple model-backed responses where the output shape is mostly
a whitelist of model fields and loaded relations. Exposers are the easier path:
less code, less ceremony, and good enough for straightforward CRUD resources.

Use transformers when the response layer needs:

- named include methods for many relations
- a shared transformation object per model
- computed fields or normalized output that should not live on the model
- Fractal serializers or manager configuration
- response shaping that must be reused outside one controller
- better control over when nested relations are included, which can improve
  performance on complex endpoints

PhalconKit REST controllers include the `Fractal` trait through `Rest`. It
provides:

- `getFractalManager()` with `RawArraySerializer` by default
- `getTransformer()` with `PhalconKit\Fractal\ModelTransformer` by default
- `setTransformer()`
- `transformModel()`
- `transformResultset()`
- `transformItem()`
- `transformCollection()`

## Module Placement

Modules register a `Transformers` namespace by convention:

```text
app/Modules/Api/Transformers/
  AbstractModelTransformer.php
  RecordTransformer.php
  RecordUserStatusTransformer.php
```

Keep transformer names aligned with model names. For example,
`RecordTransformer` should transform `App\Models\Record`.

## Loaded-Relation Includes

Avoid lazy-loading surprise queries from transformers. A good app-level base
transformer only includes a relation when the controller or model query already
loaded it.

```php
namespace App\Modules\Api\Transformers;

use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;
use Phalcon\Mvc\ModelInterface;
use PhalconKit\Fractal\Transformer;

abstract class AbstractModelTransformer extends Transformer
{
    protected function includeCollectionIfLoaded(
        ModelInterface $entity,
        string $alias,
        Transformer $transformer
    ): Collection {
        return $this->collection(
            $entity->hasDirtyRelatedAlias($alias) ? $entity->{$alias} : [],
            $transformer
        );
    }

    protected function includeItemIfLoaded(
        ModelInterface $entity,
        string $alias,
        Transformer $transformer
    ): ?Item {
        if (!$entity->hasDirtyRelatedAlias($alias)) {
            return null;
        }

        return $this->item($entity->{$alias}, $transformer);
    }
}
```

Use the exact relation alias style the app loads. Some apps use lowercase
runtime aliases such as `projectentity`; others expose generated aliases such
as `ProjectEntity`. Do not mix alias spelling between `initializeWith()`,
`defaultIncludes`, and `include*()` methods.

## Model Transformer Shape

```php
namespace App\Modules\Api\Transformers;

use App\Models\Record;
use League\Fractal\Resource\Collection;
use League\Fractal\Resource\Item;

final class RecordTransformer extends AbstractModelTransformer
{
    public array $defaultIncludes = [
        'projectentity',
        'recorduserstatuslist',
        'recordaistatuslist',
        'articlelist',
        'commentlist',
        'taglist',
        'usernode',
    ];

    public function transform(?Record $record): array
    {
        return $record?->toArray() ?? [];
    }

    public function includeProjectEntity(Record $record): ?Item
    {
        return $this->includeItemIfLoaded(
            $record,
            'projectentity',
            new ProjectTransformer()
        );
    }

    public function includeRecordUserStatusList(Record $record): Collection
    {
        return $this->includeCollectionIfLoaded(
            $record,
            'recorduserstatuslist',
            new RecordUserStatusTransformer()
        );
    }
}
```

Transformer rules:

- `transform()` should be cheap and deterministic. Start with `toArray()` and
  add computed fields only when the API contract needs them.
- `defaultIncludes` should match relations the controller normally eager-loads.
- Include methods should delegate to smaller transformers instead of embedding
  nested array construction.
- Guard includes with `hasDirtyRelatedAlias()` so missing eager loads produce
  empty/null includes instead of implicit queries.
- Avoid circular default includes. If two transformers reference each other,
  make one side optional or remove the recursive default include.

## Controller Usage

Set a transformer when a controller returns transformer-shaped output:

```php
use App\Modules\Api\Transformers\RecordTransformer;

public function initialize(): void
{
    parent::initialize();
    $this->setTransformer(new RecordTransformer());
}
```

Then use Fractal helpers in custom actions:

```php
$this->view->setVar('data', $this->transformModel($record));
$this->view->setVar('list', $this->transformCollection($records));

return $this->setRestResponse(true);
```

For standard RESTful actions, keep the app consistent: do not mix exposers and
transformers on the same endpoint unless the base controller intentionally
routes one action through Fractal and another through `expose()`.

## Transformer Checklist

When adding a transformer-backed resource:

1. Check the controller's `initializeWith()` relation graph first.
2. Match include aliases to loaded relation aliases exactly.
3. Add one transformer per nested model instead of building large nested arrays.
4. Keep sensitive fields out of `transform()` and nested transformers.
5. Confirm the serializer shape expected by clients; PhalconKit's default
   `RawArraySerializer` returns raw arrays instead of `data` wrappers.
6. Add tests for missing relation includes and loaded relation includes when
   the endpoint contract depends on them.
