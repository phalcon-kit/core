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
 * Abstract contract for search-based query conditions.
 *
 * This trait defines the public API exposed by any
 * search condition provider.
 *
 * CONDITION CONTRACT
 * ------------------
 * All search condition builders MUST return:
 *
 *  - null        → no search restriction applied
 *  - array       → [sql, bindValues, bindTypes]
 *
 * AND / OR semantics are the responsibility of the
 * implementing trait.
 */
trait AbstractSearchConditions
{
    /**
     * Initialize search conditions.
     */
    abstract public function initializeSearchConditions(): void;

    /**
     * Replace the search condition collection.
     */
    abstract public function setSearchConditions(?Collection $searchConditions): void;

    /**
     * Retrieve the registered search conditions.
     */
    abstract public function getSearchConditions(): ?Collection;

    /**
     * Build the default search condition.
     *
     * This method is expected to:
     *  - Normalize user input
     *  - Apply AND / OR grouping
     *  - Return a compiler-safe payload
     *
     * @return array|null Condition payload or null if no search applied
     */
    abstract public function buildDefaultSearchCondition(): ?array;

    /**
     * Extract normalized search terms from input.
     *
     * Exposed publicly for reuse and testability.
     *
     * @return string[]
     */
    abstract public function extractSearchTerms(): array;

    /**
     * Build an OR-group for a single search term.
     *
     * @param string $term
     * @param array  $searchFields
     * @param array  $bind
     * @param array  $bindTypes
     *
     * @return string[]
     */
    abstract public function buildSearchTermGroup(
        string $term,
        array $searchFields,
        array &$bind,
        array &$bindTypes
    ): array;
}
