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
 *
 * Search fields are opt-in: null means no search field list was configured,
 * while a non-null collection defines exactly which fields the public `search`
 * request parameter can target.
 */
trait AbstractSearchFields
{
    /**
     * Initialize the search-field policy for the current controller/action.
     */
    abstract public function initializeSearchFields(): void;
    
    /**
     * Replace the search-field policy.
     *
     * @param array|Collection|null $searchFields Field policy collection or null when
     *     search should remain unconfigured.
     */
    abstract public function setSearchFields(array|Collection|null $searchFields): void;
    
    /**
     * Return the configured search-field policy.
     *
     * @return Collection|null Field policy collection or null when search is
     *     unconfigured.
     */
    abstract public function getSearchFields(): ?Collection;
}
