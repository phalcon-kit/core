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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractQuery;

/**
 * Permission-based query condition provider.
 *
 * This trait is responsible for producing **row-level access constraints**
 * based on the current authenticated identity.
 *
 * Design contract:
 *  - Returning `null` means: *no restriction applied*
 *  - Returning an array means: *AND-applicable condition payload*
 *
 * Condition payload shape:
 *  [
 *      0 => string  $conditionSql,
 *      1 => array   $bindValues,
 *      2 => array   $bindTypes,
 *  ]
 */
trait PermissionConditions
{
    use AbstractInjectable;
    use AbstractModel;
    use AbstractQuery;

    /**
     * Registered permission condition sets.
     *
     * Keys are symbolic names (e.g. "default", "custom"),
     * values are condition payloads or callables producing them.
     */
    protected ?Collection $permissionConditions = null;

    /**
     * Initialize permission conditions.
     *
     * Called during controller / query bootstrap.
     */
    public function initializePermissionConditions(): void
    {
        $this->permissionConditions = new Collection([
            'default' => $this->buildDefaultPermissionCondition(),
        ], false);
    }

    /**
     * Replace the permission condition collection.
     *
     * @param Collection|null $permissionConditions
     */
    public function setPermissionConditions(?Collection $permissionConditions): void
    {
        $this->permissionConditions = $permissionConditions;
    }

    /**
     * Retrieve the permission condition collection.
     */
    public function getPermissionConditions(): ?Collection
    {
        return $this->permissionConditions;
    }

    /**
     * Build the default permission condition.
     *
     * Rules:
     *  - No identity → no restriction
     *  - Super roles → no restriction
     *  - No owner columns → no restriction
     *  - Otherwise → owner-based OR condition
     */
    public function buildDefaultPermissionCondition(): ?array
    {
        if (!$this->identity) {
            return null;
        }

        if ($this->identity->hasRole($this->getSuperRoles())) {
            return null;
        }

        $columns = $this->getCreatedByColumns();
        if ($columns === []) {
            return null;
        }

        return $this->buildOwnerCondition(
            (int)$this->identity->getUserId(),
            $columns
        );
    }

    /**
     * Build an owner-based permission condition.
     *
     * @param int $userId
     * @param array $columns
     *
     * @return array|null
     */
    public function buildOwnerCondition(int $userId, array $columns): ?array
    {
        $expressions = [];
        $bind = [];
        $bindTypes = [];

        foreach ($columns as $column) {
            if (!is_string($column) || $column === '') {
                continue;
            }

            $field = $this->appendModelName($column);
            $bindKey = $this->generateBindKey('permission');

            $expressions[] = "{$field} = :{$bindKey}:";
            $bind[$bindKey] = $userId;
            $bindTypes[$bindKey] = Column::BIND_PARAM_INT;
        }

        if ($expressions === []) {
            return null;
        }

        return [
            '(' . implode(' OR ', $expressions) . ')',
            $bind,
            $bindTypes,
        ];
    }

    /**
     * Columns used to assert record ownership.
     *
     * Override per-model when ownership differs.
     */
    public function getCreatedByColumns(): array
    {
        return ['createdBy'];
    }

    /**
     * Roles exempt from permission constraints.
     */
    public function getSuperRoles(): array
    {
        return ['dev', 'admin'];
    }
}
