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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions;

use Phalcon\Support\Collection;

/**
 * Abstract contract for identity-based query conditions.
 *
 * Identity conditions are responsible for constraining queries
 * based on explicit identity data (typically primary keys or
 * model-defined identity columns).
 *
 * IDENTITY CONDITION CONTRACT
 * ---------------------------
 * All identity condition builders MUST return:
 *
 *  - null        → no identity constraint applied
 *  - array       → [sql, bindValues, bindTypes]
 *
 * Returning any other shape is invalid and may break
 * the query compiler.
 *
 * IMPORTANT
 * ---------
 * Identity conditions:
 *  - Are NOT authorization rules
 *  - Are NOT implicit
 *  - Are NOT required to exist
 *
 * Consumers decide how absence of identity affects semantics.
 */
trait AbstractIdentityConditions
{
    /**
     * Initialize identity conditions.
     *
     * Called during controller / query bootstrap.
     */
    abstract public function initializeIdentityConditions(): void;

    /**
     * Replace the identity condition collection.
     *
     * Allows higher-level components to override
     * identity semantics entirely.
     */
    abstract public function setIdentityConditions(?Collection $identityConditions): void;

    /**
     * Retrieve the registered identity conditions.
     */
    abstract public function getIdentityConditions(): ?Collection;

    /**
     * Build the default identity condition.
     *
     * This method is expected to:
     *  - Pull identity data from request / parameters
     *  - Apply identity column constraints
     *  - Return a compiler-safe payload
     *
     * @return array|string|null Identity condition payload or null if none applies
     */
    abstract public function defaultIdentityCondition(): array|string|null;

    /**
     * Build an identity condition from arbitrary data.
     *
     * Implementations MUST:
     *  - Ignore missing or null values
     *  - Generate bind-safe equality predicates
     *  - AND-coalesce all applicable predicates
     *
     * Failure modes MUST be silent:
     *  - No identity columns → null
     *  - No usable data      → null
     *
     * @param array      $data    Input data (e.g. request params)
     * @param array|null $columns Optional explicit identity columns
     *
     * @return array|null Identity condition payload
     */
    abstract public function buildIdentityConditionFromData(
        array $data,
        ?array $columns = null
    ): ?array;

    /**
     * Return the identity columns for the current model.
     *
     * Default implementations usually return:
     *  - Primary key attributes
     *
     * Override for:
     *  - Composite identity models
     *  - Natural-key identity semantics
     */
    abstract public function getIdentityColumns(): array;
}
