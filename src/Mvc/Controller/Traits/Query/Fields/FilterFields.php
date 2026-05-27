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

namespace PhalconKit\Mvc\Controller\Traits\Query\Fields;

use Phalcon\Support\Collection;
use PhalconKit\Support\CollectionPolicy;

trait FilterFields
{
    /**
     * Controller-owned filter-field policy.
     *
     * Null means unrestricted filtering and preserves legacy controller
     * behavior. A non-null collection enables allow-list mode; an empty
     * collection is therefore a closed policy that rejects every client filter.
     */
    protected ?Collection $filterFields = null;
    
    /**
     * Initialize the filter-field allow-list.
     *
     * Concrete controllers can override this method and call
     * {@see setFilterFields()} to define which fields the public `filter`
     * request parameter may target. The default is null so existing resources
     * keep accepting any normalized field until they opt in to restrictions.
     */
    public function initializeFilterFields(): void
    {
        $this->setFilterFields(null);
    }
    
    /**
     * Replace the fields clients may use in the REST `filter` parameter.
     *
     * Supported collection shapes follow the filter compiler contract and may
     * include nested arrays for relation-aware filters. Passing null disables
     * allow-list enforcement; passing an empty collection keeps allow-list mode
     * active but allows no client-supplied filters.
     */
    public function setFilterFields(?Collection $filterFields): void
    {
        $this->filterFields = $filterFields;
    }
    
    /**
     * Return the configured filter-field policy.
     *
     * A null return value means unrestricted filtering. A non-null collection is
     * consumed by the filter condition builder before client filters are
     * accepted.
     */
    public function getFilterFields(): ?Collection
    {
        return $this->filterFields;
    }

    /**
     * Check whether filter-field allow-list mode is configured.
     *
     * This reports policy presence, not whether at least one field is enabled.
     * An empty collection still means filtering is intentionally closed.
     */
    public function hasFilterFields(): bool
    {
        return $this->filterFields !== null;
    }

    /**
     * Merge additional filter-field entries into the current policy.
     *
     * Merge semantics are centralized in {@see CollectionPolicy}: null starts
     * from the incoming collection, empty incoming collections leave an existing
     * policy unchanged, and associative keys can override previous entries.
     */
    public function mergeFilterFields(Collection $filterFields): void
    {
        $this->filterFields = CollectionPolicy::mergeNullable(
            $this->filterFields,
            $filterFields
        );
    }
}
