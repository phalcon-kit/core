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

namespace PhalconKit\Mvc\Controller\Traits\Query\Conditions;

use Phalcon\Db\Column;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;

/**
 * IdentityConditions
 *
 * Responsibility:
 *  - Generate *identity-scoped* WHERE conditions based on runtime parameters
 *  - Constrain queries to a model’s identity columns (usually primary keys)
 *
 * Design constraints:
 *  - Stateless builder: no side effects beyond stored Collection
 *  - Null-safe: absence of identity data yields no condition
 *  - PDO-safe: all values bound with generated placeholders
 *
 * Output contract:
 *  - null → no identity constraint
 *  - [string, bind[], types[]] → Phalcon-compatible condition payload
 *
 * This trait intentionally does NOT:
 *  - Perform authorization decisions
 *  - Infer identity values implicitly
 *  - Throw when identity data is missing
 *
 * Consumers decide how absence of identity affects query semantics.
 */
trait IdentityConditions
{
    use AbstractModel;
    use AbstractParams;
    use AbstractQuery;

    /**
     * Collection of named identity conditions.
     *
     * Shape:
     *  [
     *      'default' => array|string|null
     *  ]
     */
    protected ?Collection $identityConditions = null;

    /**
     * Initializes identity conditions.
     *
     * Called during controller/query bootstrap.
     * Always registers a `default` condition, which may resolve to null.
     */
    public function initializeIdentityConditions(): void
    {
        $this->setIdentityConditions(new Collection([
            'default' => $this->defaultIdentityCondition(),
        ], false));
    }

    /**
     * Explicit setter.
     *
     * Allows higher-level components to override identity semantics.
     */
    public function setIdentityConditions(?Collection $identityConditions): void
    {
        $this->identityConditions = $identityConditions;
    }

    /**
     * Returns the registered identity conditions collection.
     */
    public function getIdentityConditions(): ?Collection
    {
        return $this->identityConditions;
    }

    /**
     * Resolves the default identity condition.
     *
     * Uses request/query parameters as the identity source.
     */
    public function defaultIdentityCondition(): array|string|null
    {
        return $this->buildIdentityConditionFromData(
            $this->getParams()
        );
    }

    /**
     * Builds an identity condition from arbitrary data.
     *
     * Algorithm:
     *  1. Resolve identity columns (defaults to primary key attributes)
     *  2. For each column:
     *     - Skip if missing or null in input data
     *     - Generate a unique bind placeholder
     *     - Append strict equality predicate
     *  3. AND-coalesce predicates
     *
     * Failure modes:
     *  - No identity columns → null
     *  - No matching data provided → null
     *
     * @param array $data Input data (typically request params)
     * @param array|null $columns Explicit identity columns override
     *
     * @return array|string|null
     *  [
     *      string $condition,
     *      array $bind,
     *      array $bindTypes
     *  ]
     */
    public function buildIdentityConditionFromData(
        array $data,
        ?array $columns = null
    ): ?array {
        $columns ??= $this->getIdentityColumns();

        if ($columns === []) {
            return null;
        }

        $conditions = [];
        $bind = [];
        $bindTypes = [];

        foreach ($columns as $column) {
            if (!array_key_exists($column, $data)) {
                continue;
            }

            $value = $data[$column];

            if ($value === null) {
                continue;
            }

            $field = $this->appendModelName($column);
            $bindKey = $this->generateBindKey('identity');

            $conditions[] = "{$field} = :{$bindKey}:";
            $bind[$bindKey] = $value;
            $bindTypes[$bindKey] = Column::BIND_PARAM_STR;
        }

        if ($conditions === []) {
            return null;
        }

        return [
            '(' . implode(') and (', $conditions) . ')',
            $bind,
            $bindTypes,
        ];
    }

    /**
     * Returns the identity columns for the current model.
     *
     * Default strategy:
     *  - Use primary key attributes
     *
     * Override point:
     *  - Models with composite or non-PK identity semantics
     */
    public function getIdentityColumns(): array
    {
        return $this->getPrimaryKeyAttributes();
    }
}
