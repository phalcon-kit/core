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
 * Abstract contract for fields that may participate in text search.
 */
trait AbstractSearchFields
{
    /**
     * Initialize search field policy.
     */
    abstract public function initializeSearchFields(): void;
    
    /**
     * Replace search field policy.
     */
    abstract public function setSearchFields(?Collection $searchFields): void;
    
    /**
     * Return search field policy.
     */
    abstract public function getSearchFields(): ?Collection;
}
