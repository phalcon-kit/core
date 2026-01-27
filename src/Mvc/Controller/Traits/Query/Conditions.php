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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Filter\Exception;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractConditions;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions\FilterConditions;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions\IdentityConditions;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions\PermissionConditions;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions\SearchConditions;
use PhalconKit\Mvc\Controller\Traits\Query\Conditions\SoftDeleteConditions;
use PhalconKit\Support\CollectionPolicy;

trait Conditions
{
    use AbstractConditions;
    
    use FilterConditions;
    use IdentityConditions;
    use PermissionConditions;
    use SearchConditions;
    use SoftDeleteConditions;
    
    protected ?Collection $conditions = null;

    /**
     * Initializes and sets up various conditions required for the system.
     * - Permission Conditions
     * - Soft Delete Conditions
     * - Identity Conditions
     * - Filter Conditions
     * - Search Conditions
     *
     * @return void
     * @throws Exception
     */
    public function initializeConditions(): void
    {
        $this->initializePermissionConditions();
        $this->initializeSoftDeleteConditions();
        $this->initializeIdentityConditions();
        $this->initializeFilterConditions();
        $this->initializeSearchConditions();
        
        $this->setConditions(new Collection([
            'permission' => $this->getPermissionConditions(),
            'softDelete' => $this->getSoftDeleteConditions(),
            'identity' => $this->getIdentityConditions(),
            'filter' => $this->getFilterConditions(),
            'search' => $this->getSearchConditions(),
        ], false));
    }

    /**
     * Sets the conditions.
     *
     * @param Collection|null $conditions A collection of conditions or null to unset the conditions.
     * @return void
     */
    public function setConditions(?Collection $conditions): void
    {
        $this->conditions = $conditions;
    }

    /**
     * Retrieves the conditions.
     *
     * @return Collection|null A collection of conditions or null if none are set.
     */
    public function getConditions(): ?Collection
    {
        return $this->conditions;
    }

    /**
     * Merges the provided conditions collection with the current conditions property.
     *
     * @param Collection $conditions The collection of conditions to merge with the current property.
     * @return void
     */
    public function mergeConditions(Collection $conditions): void
    {
        $this->conditions = CollectionPolicy::mergeNullable(
            $this->conditions,
            $conditions
        );
    }
}
