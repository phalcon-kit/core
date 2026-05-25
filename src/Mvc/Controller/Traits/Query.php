<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Controller\Traits;

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection;
use Phalcon\Mvc\Model\ResultsetInterface;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;
use PhalconKit\Mvc\Controller\Traits\Query\Bind;
use PhalconKit\Mvc\Controller\Traits\Query\Cache;
use PhalconKit\Mvc\Controller\Traits\Query\Column;
use PhalconKit\Mvc\Controller\Traits\Query\Compiler;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions;
use PhalconKit\Mvc\Controller\Traits\Query\Distinct;
use PhalconKit\Mvc\Controller\Traits\Query\DynamicJoins;
use PhalconKit\Mvc\Controller\Traits\Query\Fields;
use PhalconKit\Mvc\Controller\Traits\Query\Group;
use PhalconKit\Mvc\Controller\Traits\Query\Having;
use PhalconKit\Mvc\Controller\Traits\Query\Joins;
use PhalconKit\Mvc\Controller\Traits\Query\Limit;
use PhalconKit\Mvc\Controller\Traits\Query\Offset;
use PhalconKit\Mvc\Controller\Traits\Query\Order;
use PhalconKit\Mvc\Controller\Traits\Query\Save;
use PhalconKit\Mvc\Controller\Traits\Query\With;
use PhalconKit\Mvc\Model\Interfaces\EagerLoadInterface;
use PhalconKit\Support\Helper;

/**
 * Shared REST query builder for PhalconKit controllers.
 *
 * The trait coordinates request-driven query state: filters, permissions,
 * joins, eager-loading, grouping, aggregate columns, pagination, ordering,
 * cache options, and save payload metadata. It compiles those collections into
 * Phalcon model `find()`/aggregate option arrays while keeping extension hooks
 * available through REST initialization events.
 *
 * @see https://docs.phalcon.io/5.13/db-models/
 * @see https://docs.phalcon.io/5.13/db-models-relationships/
 */
trait Query
{
    use AbstractQuery;
    
    use AbstractModel;
    
    use Bind;
    use Cache;
    use Column;
    use Compiler;
    use Conditions;
    use Distinct;
    use DynamicJoins;
    use Fields;
    use Group;
    use Having;
    use Joins;
    use Limit;
    use Offset;
    use Order;
    use Save;
    use With;
    
    protected ?Collection $find = null;
    
    /**
     * Initializes the query builder with default values for various properties.
     *
     * @throws FilterException When request parameter filtering fails during
     *     query initialization.
     */
    public function initializeQuery(): void
    {
        $this->eventsManager->fire('rest:beforeInitializeQuery', $this);
        
        $this->initializeCacheConfig();
        $this->eventsManager->fire('rest:afterInitializeCacheConfig', $this);
        
        // FIELDS (expose, filter, map, save, search)
        $this->initializeFields();
        $this->eventsManager->fire('rest:afterInitializeFields', $this);
        
        // JOINS
        $this->initializeJoins();
        $this->eventsManager->fire('rest:afterInitializeJoins', $this);

        // DYNAMIC JOINS
        $this->initializeDynamicJoins();
        $this->eventsManager->fire('rest:afterInitializeDynamicJoins', $this);

        // WHERE
        $this->initializeConditions();
        $this->eventsManager->fire('rest:afterInitializeConditions', $this);
        
        // SELECT-level modifier
        $this->initializeDistinct();
        $this->eventsManager->fire('rest:afterInitializeDistinct', $this);
        
        // GROUP BY
        $this->initializeGroup();
        $this->eventsManager->fire('rest:afterInitializeGroup', $this);
        
        // HAVING
        $this->initializeHaving();
        $this->eventsManager->fire('rest:afterInitializeHaving', $this);
        
        // ORDER
        $this->initializeOrder();
        $this->eventsManager->fire('rest:afterInitializeOrder', $this);
        
        // LIMIT
        $this->initializeLimit();
        $this->eventsManager->fire('rest:afterInitializeLimit', $this);
        
        // OFFSET
        $this->initializeOffset();
        $this->eventsManager->fire('rest:afterInitializeOffset', $this);
        
        // Eager-loading, post-query shaping
        $this->initializeWith();
        $this->eventsManager->fire('rest:afterInitializeWith', $this);
        
        // BIND
        $this->initializeBind();
        $this->eventsManager->fire('rest:afterInitializeBind', $this);
        
        // TYPES
        $this->initializeBindTypes();
        $this->eventsManager->fire('rest:afterInitializeBindTypes', $this);
        
        // Final assembly/execution wrapper
        $this->initializeFind();
        $this->eventsManager->fire('rest:afterInitializeFind', $this);
        
        $this->eventsManager->fire('rest:afterInitializeQuery', $this);
    }
    
    /**
     * Initializes the `find` property with a new Collection object.
     * The values of various properties are assigned to the corresponding keys of the Collection object.
     *
     * @return void
     */
    public function initializeFind(): void
    {
        $this->setFind(new Collection([
            'conditions' => $this->getConditions(),
            'bind' => $this->getBind(),
            'bindTypes' => $this->getBindTypes(),
            'limit' => $this->getLimit(),
            'offset' => $this->getOffset(),
            'order' => $this->getOrder(),
            'column' => $this->getColumn(),
            'distinct' => $this->getDistinct(),
            'joins' => $this->getJoins(),
            'group' => $this->getGroup(),
            'having' => $this->getHaving(),
            'cache' => $this->getCacheConfig(),
        ]));
    }
    
    /**
     * Sets the value of the `find` property.
     *
     * @param Collection|null $find The new value for the `find` property.
     * @return void
     */
    public function setFind(?Collection $find): void
    {
        $this->find = $find;
    }
    
    /**
     * Retrieves the value of the `find` property.
     *
     * @return Collection|null The value of the `find` property.
     */
    public function getFind(): ?Collection
    {
        return $this->find;
    }

    /**
     * Builds the `find` array for a query.
     *
     * @param Collection|null $find The collection to build the find array from. Defaults to null.
     * @param bool $ignoreKey Whether to ignore the keys in the collection. Defaults to false.
     * @return array The built find array.
     */
    public function prepareFind(?Collection $find = null, bool $ignoreKey = false): array
    {
        $find ??= $this->getFind();
        
        if ($find === null) {
            return [];
        }
        
        $build = $this->prepareCollectionToCompile($find);

        foreach (['column', 'distinct', 'group', 'order'] as $keyToJoin) {
            if (isset($build[$keyToJoin]) && is_array($build[$keyToJoin])) {
                $build[$keyToJoin] = $this->prepareFindListToString($build[$keyToJoin]);
                if ($build[$keyToJoin] === '') {
                    unset($build[$keyToJoin]);
                }
            }
        }

        // Join Normalization (to support added conditions with bind and bindTypes)
        if (!empty($build['joins']) && is_array($build['joins'])) {
            $normalizedJoins = $this->normalizeJoins($build['joins']);

            // Replace joins with pure Phalcon joins (payload stripped, ON merged)
            $build['joins'] = $normalizedJoins['joins'];

            // Merge join-scoped bind data into global bind
            if (!empty($normalizedJoins['bind'])) {
                $build['bind'] = array_merge($build['bind'] ?? [], $normalizedJoins['bind']);
            }

            // Merge join-scoped bindTypes into global bindTypes
            if (!empty($normalizedJoins['bindTypes'])) {
                $build['bindTypes'] = array_merge($build['bindTypes'] ?? [], $normalizedJoins['bindTypes']);
            }
        }

        return $this->compileFind($build);
    }

    /**
     * Converts find list options to their PHQL string form.
     *
     * Collection-backed query options can be represented either as plain values
     * or as enabled field maps, for example ['id' => true]. Values remain the
     * default source, but true map entries use their string key as the selected
     * field instead of compiling to "1".
     */
    protected function prepareFindListToString(array $items): string
    {
        $list = [];
        foreach ($items as $key => $value) {
            if ($value === true && is_string($key)) {
                $list[] = $key;
                continue;
            }

            $list[] = $value;
        }

        return trim(implode(', ', Helper::flatten($list)));
    }
    
    /**
     * Determines whether WHERE conditions must be promoted to HAVING.
     *
     * Currently disabled by design.
     */
    public function conditionsShouldBeHaving(?string $conditions): bool
    {
        return false;
    }
    
    /**
     * Find records in the database using the specified criteria.
     *
     * @param array|null $find Optional. An array of criteria to determine the records to find.
     *                         If not provided, the default criteria from `getFind()` method
     *                         will be used. Defaults to `null`.
     *
     * @return ResultsetInterface&\Traversable The result of the find operation.
     */
    public function find(?array $find = null): ResultsetInterface
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::find($find);
    }
    
    /**
     * Find records in the database using the specified criteria and include related records.
     *
     * @param array|null $with Optional. An array of related models to include
     *                         with the found records. Defaults to `null`.
     * @param array|null $find Optional. An array of criteria to determine the records to find.
     *                         If not provided, the default criteria from `getFind()` method
     *                         will be used. Defaults to `null`.
     *
     * @return array The result of the find operation with loaded relationships.
     * @throws ServiceException When the configured model does not support
     *     PhalconKit eager-loading helpers.
     */
    public function findWith(?array $with = null, ?array $find = null): array
    {
        $find ??= $this->prepareFind();
        $with ??= $this->getWith()?->toArray() ?? [];
        $model = $this->requireEagerLoadModel($this->loadModel(), 'findWith');
        return $model::findWith($with, $find);
    }
    
    /**
     * Find the first record in the database using the specified criteria.
     *
     * Note: We intentionally removed the Row from the return type to simplify usages.
     * If you need to access the Row, use a query builder instead.
     *
     * @param array|null $find Optional. An array of criteria to determine the record to find.
     *                         If not provided, the default criteria from `getFind()` method
     *                         will be used to find the first record. Defaults to `null`.
     *
     * @return ModelInterface|false|null The result of the find operation, which is the first record that matches the criteria.
     */
    public function findFirst(?array $find = null): ModelInterface|false|null
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::findFirst($find);
    }
    
    /**
     * Find the first record in the database using the specified criteria and relations.
     *
     * @param array|null $with Optional. An array of relations to eager load for the record.
     *                         If not provided, the default relations from `getWith()` method
     *                         will be used. Defaults to `null`.
     * @param array|null $find Optional. An array of criteria to determine the records to find.
     *                         If not provided, the default criteria from `getFind()` method
     *                         will be used. Defaults to `null`.
     *
     * @return ?ModelInterface The result of the find operation for the first record.
     * @throws ServiceException When the configured model does not support
     *     PhalconKit eager-loading helpers.
     */
    public function findFirstWith(?array $with = null, ?array $find = null): ?ModelInterface
    {
        $find ??= $this->prepareFind();
        $with ??= $this->getWith()?->toArray() ?? [];
        $model = $this->requireEagerLoadModel($this->loadModel(), 'findFirstWith');
        return $model::findFirstWith($with, $find);
    }

    /**
     * Require a loaded model that supports PhalconKit eager-loading helpers.
     *
     * Controller query helpers can load any Phalcon model, but `findWith()` and
     * `findFirstWith()` need the PhalconKit eager-loading contract. Keeping this
     * check in one helper keeps the public query methods readable while still
     * producing a stable service-resolution exception instead of a late static
     * method error when a controller is wired to the wrong model class.
     *
     * @param ModelInterface $model Loaded model instance used for static query
     *     dispatch.
     * @param string $method Query helper that requires eager loading.
     *
     * @return EagerLoadInterface The same model narrowed to the eager-loading
     *     contract.
     *
     * @throws ServiceException When the configured model does not support
     *     PhalconKit eager-loading helpers.
     */
    protected function requireEagerLoadModel(ModelInterface $model, string $method): EagerLoadInterface
    {
        if ($model instanceof EagerLoadInterface) {
            return $model;
        }

        throw new ServiceException(sprintf(
            'Configured model "%s" must implement "%s" to use %s().',
            $model::class,
            EagerLoadInterface::class,
            $method
        ));
    }
    
    /**
     * Calculates the average value based on a given set of criteria.
     *
     * @param array|null $find The criteria to filter the records by (optional).
     * @return ResultsetInterface|float|false The average value or a result set containing the average value.
     */
    public function average(?array $find = null): ResultsetInterface|float|false
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::average($this->getCalculationFind($find));
    }
    
    /**
     * Retrieves the total count of items based on the specified model name and find criteria.
     * Note: limit and offset are removed from the parameters in order to retrieve the total count
     *
     * @param array|null $find An array of find criteria to filter the results. If null, the default criteria will be applied.
     *
     * @return ResultsetInterface|int|false The total count of items that match the specified criteria.
     */
    public function count(?array $find = null): ResultsetInterface|int|false
    {
        $find ??= $this->prepareFind();
        $find = $this->getCalculationFind($find);
        $find = $this->prepareCountFind($find);

        return $this->loadModel()::count($find);
    }

    /**
     * Prepare count-specific options without overriding an explicit count column.
     */
    protected function prepareCountFind(array $find): array
    {
        if (!empty($find['joins']) && !array_key_exists('column', $find)) {
            $column = $this->getJoinedCountColumn($find);
            if ($column !== null) {
                $find['column'] = $column;
            }
        }

        return $find;
    }

    /**
     * Joined count queries default to the root model identity for single-column primary keys.
     */
    protected function getJoinedCountColumn(array $find): ?string
    {
        $primaryKeyAttributes = $this->getPrimaryKeyAttributes();
        if (count($primaryKeyAttributes) !== 1) {
            return null;
        }

        $primaryKey = reset($primaryKeyAttributes);
        return is_string($primaryKey) && $primaryKey !== ''
            ? 'DISTINCT ' . $this->appendModelName($primaryKey)
            : null;
    }
    
    /**
     * Calculates the sum of values based on a given search criteria.
     *
     * @param array|null $find Optional: The criteria to find the maximum value from.
     *                         Default: null (will retrieve the `find` from $this->getFind())
     *
     * @return ResultsetInterface|float|false The calculated sum of values.
     */
    public function sum(?array $find = null): ResultsetInterface|float|false
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::sum($this->getCalculationFind($find));
    }
    
    /**
     * Retrieves the minimum value.
     *
     * @param array|null $find Optional: The criteria to find the maximum value from.
     *                         Default: null (will retrieve the `find` from $this->getFind())
     *
     * @return ResultsetInterface|float|false The maximum value from the dataset or a `ResultsetInterface` that represents the grouped maximum values.
     */
    public function maximum(?array $find = null): ResultsetInterface|float|false
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::maximum($this->getCalculationFind($find));
    }
    
    /**
     * Retrieves the minimum value.
     *
     * @param array|null $find Optional: The criteria to find the minimum value from.
     *                         Default: null (will retrieve the `find` from $this->getFind())
     *
     * @return ResultsetInterface|float|false The minimum value from the dataset or a `ResultsetInterface` that represents the grouped minimum values.
     */
    public function minimum(?array $find = null): ResultsetInterface|float|false
    {
        $find ??= $this->prepareFind();
        return $this->loadModel()::minimum($this->getCalculationFind($find));
    }
    
    /**
     * Prepares and retrieves the modified `find` array with optional adjustments.
     *
     * @param array|null $find The initial `find` array to modify. If null, it defaults
     *                         to the result of `getFind()->toArray()` or an empty array.
     * @param bool $removeLimitOffset Whether to remove `limit` and `offset` keys
     *                                from the `find` array. Defaults to true.
     * @return array The adjusted `find` array, filtered with any necessary modifications.
     */
    protected function getCalculationFind(?array $find = null, bool $removeLimitOffset = true): array
    {
        $find ??= $this->getFind()?->toArray() ?? [];
        
        // remove limit
        if ($removeLimitOffset) {
            if (isset($find['limit'])) {
                unset($find['limit']);
            }
            
            // remove offset
            if (isset($find['offset'])) {
                unset($find['offset']);
            }
        }
        
        // calculation columns fail when the group is an array, combine it to a string
        if (isset($find['group']) && is_array($find['group'])) {
            $find['group'] = implode(', ', $find['group']);
        }

        return array_filter(
            $find,
            static fn(mixed $value): bool => $value !== null && $value !== '' && $value !== []
        );
    }
    
    /**
     * Generates a unique bind key with the given prefix.
     *
     * @param string $prefix The prefix to be used in the bind key.
     *
     * @return string The generated bind key.
     */
    public function generateBindKey(string $prefix): string
    {
        return '_' . uniqid($prefix . '_') . '_';
    }
}
