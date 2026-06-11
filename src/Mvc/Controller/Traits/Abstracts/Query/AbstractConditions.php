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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query;

use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions\AbstractFilterConditions;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions\AbstractIdentityConditions;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions\AbstractPermissionConditions;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions\AbstractSearchConditions;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Conditions\AbstractSoftDeleteConditions;

/**
 * Abstract contract for the composed REST query condition collections.
 *
 * The concrete condition stack combines permission, soft-delete, identity,
 * request-filter, and search conditions into one compiler input collection.
 */
trait AbstractConditions
{
    use AbstractFilterConditions;
    use AbstractIdentityConditions;
    use AbstractPermissionConditions;
    use AbstractSearchConditions;
    use AbstractSoftDeleteConditions;
    
    /**
     * Initialize all condition collections.
     */
    abstract public function initializeConditions(): void;
    
    /**
     * Replace the composed condition collection.
     */
    abstract public function setConditions(array|Collection|null $conditions): void;
    
    /**
     * Return the composed condition collection.
     */
    abstract public function getConditions(): ?Collection;
}
