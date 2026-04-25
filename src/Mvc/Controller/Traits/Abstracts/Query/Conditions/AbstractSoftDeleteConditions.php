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
 * Abstract contract for soft-delete query conditions.
 *
 * Soft-delete conditions are responsible for excluding
 * logically deleted records from query results.
 *
 * CONDITION CONTRACT
 * ------------------
 * Implementations MUST return:
 *
 *  - null        → no soft-delete constraint applied
 *  - array       → [sql, bindValues, bindTypes]
 *
 * The query compiler depends on this behavior strictly.
 */
trait AbstractSoftDeleteConditions
{
    /**
     * Initialize soft-delete conditions.
     *
     * Called during controller / query bootstrap.
     */
    abstract public function initializeSoftDeleteConditions(): void;

    /**
     * Replace the soft-delete condition collection.
     */
    abstract public function setSoftDeleteConditions(?Collection $softDeleteConditions): void;

    /**
     * Retrieve the registered soft-delete conditions.
     */
    abstract public function getSoftDeleteConditions(): ?Collection;

    /**
     * Build the default soft-delete condition.
     *
     * @return array|null Soft-delete condition payload
     */
    abstract public function buildDefaultSoftDeleteCondition(): ?array;

    /**
     * Return the column used to mark deleted records.
     *
     * Returning null MUST disable soft-delete filtering.
     */
    abstract public function getSoftDeleteColumn(): ?string;

    /**
     * Return the value representing a non-deleted record.
     *
     * Example:
     *  - 0 (boolean flag)
     *  - null (nullable timestamp strategies may override logic)
     */
    abstract public function getSoftDeleteActiveValue(): int;
}
