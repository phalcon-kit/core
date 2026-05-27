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
 *
 * A null policy keeps filtering unrestricted for backward compatibility. A
 * non-null collection enables allow-list mode, and an empty collection is an
 * explicit closed policy.
 */
trait AbstractFilterFields
{
    /**
     * Initialize the filter-field policy for the current controller/action.
     */
    abstract public function initializeFilterFields(): void;
    
    /**
     * Replace the filter-field policy.
     *
     * @param Collection|null $filterFields Field policy collection, null for
     *     unrestricted filtering, or an empty collection for a closed policy.
     */
    abstract public function setFilterFields(?Collection $filterFields): void;
    
    /**
     * Return the configured filter-field policy.
     *
     * @return Collection|null Field policy collection or null for unrestricted
     *     filtering.
     */
    abstract public function getFilterFields(): ?Collection;
}
