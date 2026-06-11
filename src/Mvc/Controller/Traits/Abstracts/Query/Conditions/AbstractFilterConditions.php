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
 * Abstract contract for request-filter query conditions.
 *
 * Implementations convert user-supplied filter trees into compiler-safe SQL,
 * bind values, and bind types. Filter conditions are distinct from permission
 * and identity conditions: they express client-requested narrowing only.
 */
trait AbstractFilterConditions
{
    /**
     * Initialize filter-condition definitions.
     */
    abstract public function initializeFilterConditions(): void;
    
    /**
     * Replace filter-condition definitions.
     */
    abstract public function setFilterConditions(array|Collection|null $filterConditions): void;
    
    /**
     * Return filter-condition definitions.
     */
    abstract public function getFilterConditions(): ?Collection;
    
    /**
     * Build the default request-filter condition.
     *
     * @param array<string|int, mixed>|null $filters Filter tree. Null means
     *     read request filters from the controller.
     * @param array<string|int, mixed>|null $allowedFilters Allowed field map.
     *     Null means use the controller's filter-field policy.
     * @param string|null $aliasContext Optional alias context for nested joins.
     * @param bool $or Whether the current filter level uses OR semantics.
     * @param int $level Current nesting level.
     *
     * @return array<string|int, mixed>|string|null Compiler-safe condition.
     */
    abstract public function defaultFilterCondition(
        ?array $filters = null,
        ?array $allowedFilters = null,
        ?string $aliasContext = null,
        bool $or = false,
        int $level = 0
    ): array|string|null;
    
    /**
     * Normalize a filter operator alias to the compiler's canonical operator.
     */
    abstract public function normalizeFilterOperator(string $operator): string;
    
    /**
     * Infer the Phalcon bind type for a raw filter value.
     */
    abstract public function getBindTypeFromRawValue(mixed $rawValue = null): int;
}
