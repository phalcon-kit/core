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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields;

use Phalcon\Support\Collection;

/**
 * Abstract contract for fields that may appear in request filters.
 */
trait AbstractFilterFields
{
    /**
     * Initialize filter field policy.
     */
    abstract public function initializeFilterFields(): void;
    
    /**
     * Replace filter field policy.
     */
    abstract public function setFilterFields(?Collection $filterFields): void;
    
    /**
     * Return filter field policy.
     */
    abstract public function getFilterFields(): ?Collection;
}
