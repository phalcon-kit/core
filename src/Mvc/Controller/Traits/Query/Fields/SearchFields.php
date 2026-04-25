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
    protected ?Collection $searchFields = null;
    
    /**
     * Initializes the search fields.
     *
     * This method is responsible for initializing the necessary search fields for the model
     *
     * @return void
     */
    public function initializeSearchFields(): void
    {
        $this->setSearchFields(null);
    }
    
    /**
     * Sets the fields for searching data.
     *
     * @param Collection|null $searchFields The array of search fields.
     *                                      Pass null to allow searching all fields.
     */
    public function setSearchFields(?Collection $searchFields): void
    {
        $this->searchFields = $searchFields;
    }
    
    /**
     * Returns the search fields.
     *
     * This method retrieves the search fields for the model.
     * If search fields have been set, it returns the collection of search fields.
     * If no search fields have been set, it returns null.
     *
     * Note: The search fields are the fields that are used with the search queries.
     *
     * @return Collection|null The collection of search fields or null if no search fields have been set.
     */
    public function getSearchFields(): ?Collection
    {
        return $this->searchFields;
    }

    /**
     * Determines if the search fields are defined.
     *
     * This method checks whether the search fields for the model have been set.
     *
     * @return bool True if search fields are defined, false otherwise.
     */
    public function hasSearchFields(): bool
    {
        return $this->searchFields !== null;
    }

    /**
     * Merges the provided searchFields collection with the current searchFields property.
     *
     * @param Collection $searchFields The collection of searchFields to merge with the current property.
     * @return void
     */
    public function mergeSearchFields(Collection $searchFields): void
    {
        $this->searchFields = CollectionPolicy::mergeNullable(
            $this->searchFields,
            $searchFields
        );
    }
}
