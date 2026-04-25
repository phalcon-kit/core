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
 * Abstract contract for permission-based query conditions.
 *
 * This trait defines the minimum API required for any
 * permission condition provider participating in the
 * query compilation pipeline.
 *
 * CONDITION CONTRACT
 * ------------------
 * All permission condition builders MUST return:
 *
 *  - null        → no restriction applied
 *  - array       → [sql, bindValues, bindTypes]
 *
 * Any other return shape is considered invalid and
 * may break the query compiler.
 */
trait AbstractPermissionConditions
{
    /**
     * Initialize permission conditions.
     *
     * Called during controller / query bootstrap.
     */
    abstract public function initializePermissionConditions(): void;

    /**
     * Replace the permission condition collection.
     */
    abstract public function setPermissionConditions(?Collection $permissionConditions): void;

    /**
     * Retrieve the registered permission conditions.
     */
    abstract public function getPermissionConditions(): ?Collection;

    /**
     * Build the default permission condition.
     *
     * This method MUST be deterministic and side-effect free.
     *
     * @return array|null Condition payload or null if unrestricted
     */
    abstract public function buildDefaultPermissionCondition(): ?array;

    /**
     * Return the list of ownership columns used to restrict access.
     *
     * Example:
     *  - ['createdBy']
     *  - ['ownerId', 'assignedTo']
     */
    abstract public function getCreatedByColumns(): array;

    /**
     * Return the list of roles exempt from permission constraints.
     */
    abstract public function getSuperRoles(): array;
}
