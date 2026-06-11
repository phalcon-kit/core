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

trait SearchFields
{
    /**
     * Controller-owned search-field policy.
     *
     * Null means the search condition builder has no configured field list and
     * will not add a search predicate. A non-null collection defines the fields
     * considered by the public `search` request parameter.
     */
    protected ?Collection $searchFields = null;
    
    /**
     * Initialize the full-text-like search field list.
     *
     * Concrete controllers should override this method and call
     * {@see setSearchFields()} when a resource supports request-driven search.
     * The default is null so search stays disabled unless a controller defines
     * an explicit set of searchable fields.
     */
    public function initializeSearchFields(): void
    {
        $this->setSearchFields(null);
    }
    
    /**
     * Replace the fields used by the REST `search` parameter.
     *
     * Passing null disables search field configuration. Passing an empty
     * collection keeps the policy explicit but gives the search builder no
     * fields to compile.
     */
    public function setSearchFields(array|Collection|null $searchFields): void
    {
        $this->searchFields = CollectionPolicy::normalizeNullable($searchFields);
    }
    
    /**
     * Return the configured search-field policy.
     *
     * A null return value means no search fields have been configured. A
     * non-null collection is flattened by the search condition builder so nested
     * field definitions can participate in the generated predicate.
     */
    public function getSearchFields(): ?Collection
    {
        return $this->searchFields;
    }

    /**
     * Check whether search-field configuration is present.
     *
     * This reports policy presence only. An empty collection still means the
     * controller made an explicit search-field decision.
     */
    public function hasSearchFields(): bool
    {
        return $this->searchFields !== null;
    }

    /**
     * Merge additional search-field entries into the current policy.
     *
     * Merge semantics are centralized in {@see CollectionPolicy}: null starts
     * from the incoming collection, empty incoming collections leave an existing
     * policy unchanged, and associative keys can override previous entries.
     */
    public function mergeSearchFields(array|Collection $searchFields): void
    {
        $this->searchFields = CollectionPolicy::mergeNullable(
            $this->searchFields,
            CollectionPolicy::normalize($searchFields)
        );
    }
}
