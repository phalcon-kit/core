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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;

/**
 * Soft-delete query condition provider.
 *
 * PURPOSE
 * -------
 * This trait enforces soft-delete semantics at the query level
 * by excluding records marked as deleted.
 *
 * It does NOT:
 *  - Perform delete operations
 *  - Decide whether soft-delete is enabled globally
 *  - Infer delete state implicitly
 *
 * CONDITION CONTRACT
 * ------------------
 *  - null  → no soft-delete constraint applied
 *  - array → [sql, bindValues, bindTypes]
 *
 * The compiler relies on this contract strictly.
 */
trait SoftDeleteConditions
{
    use AbstractModel;
    use AbstractQuery;

    /**
     * Registered soft-delete condition sets.
     *
     * Allows consumers to:
     *  - Disable soft delete
     *  - Replace default behavior
     *  - Introduce advanced delete semantics
     */
    protected ?Collection $softDeleteConditions = null;

    /**
     * Initialize soft-delete conditions.
     *
     * Called during controller / query bootstrap.
     * Always registers a `default` condition, which may resolve to null.
     */
    public function initializeSoftDeleteConditions(): void
    {
        $this->softDeleteConditions = new Collection(
            [
                'default' => $this->buildDefaultSoftDeleteCondition(),
            ],
            false
        );
    }

    /**
     * Replace the soft-delete condition collection.
     */
    public function setSoftDeleteConditions(?Collection $softDeleteConditions): void
    {
        $this->softDeleteConditions = $softDeleteConditions;
    }

    /**
     * Retrieve the registered soft-delete conditions.
     */
    public function getSoftDeleteConditions(): ?Collection
    {
        return $this->softDeleteConditions;
    }

    /**
     * Build the default soft-delete condition.
     *
     * DEFAULT STRATEGY
     * ----------------
     *  - Column: `deleted`
     *  - Value:  0
     *  - Comparison: strict equality
     *
     * This assumes:
     *  - 0 = not deleted
     *  - 1 = deleted
     *
     * Override this method for:
     *  - nullable soft delete
     *  - timestamp-based delete
     *  - multi-state delete flags
     *
     * @return array|null Soft-delete condition payload
     */
    public function buildDefaultSoftDeleteCondition(): ?array
    {
        $column = $this->getSoftDeleteColumn();
        if ($column === null) {
            // Soft delete disabled for this model
            return null;
        }

        $field = $this->appendModelName($column);
        $bindKey = $this->generateBindKey('deleted');

        return [
            "{$field} = :{$bindKey}:",
            [$bindKey => $this->getSoftDeleteActiveValue()],
            [$bindKey => Column::BIND_PARAM_INT],
        ];
    }

    /**
     * Return the column used to mark deleted records.
     *
     * Returning null disables soft-delete constraints entirely.
     */
    public function getSoftDeleteColumn(): ?string
    {
        return 'deleted';
    }

    /**
     * Return the value representing a non-deleted record.
     *
     * Default:
     *  - 0 → active
     */
    public function getSoftDeleteActiveValue(): int
    {
        return 0;
    }
}
