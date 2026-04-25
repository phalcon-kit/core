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
    protected ?Collection $filterFields = null;
    
    /**
     * Initializes the filter fields.
     *
     * This method is responsible for initializing the necessary filter fields for the model
     *
     * @return void
     */
    public function initializeFilterFields(): void
    {
        $this->setFilterFields(null);
    }
    
    /**
     * Sets the fields for filtering data.
     *
     * @param Collection|null $filterFields The array of filter fields.
     *                                      Pass null to allow filtering all fields.
     */
    public function setFilterFields(?Collection $filterFields): void
    {
        $this->filterFields = $filterFields;
    }
    
    /**
     * Returns the filter fields.
     *
     * This method retrieves the filter fields for the model.
     * If filter fields have been set, it returns the collection of filter fields.
     * If no filter fields have been set, it returns null.
     *
     * Note: The filter fields are the fields that are allowed to be used within database queries.
     *
     * @return Collection|null The collection of filter fields or null if no filter fields have been set.
     */
    public function getFilterFields(): ?Collection
    {
        return $this->filterFields;
    }

    /**
     * Determines if filter fields are set.
     *
     * This method checks whether the filter fields have been initialized
     * or are set to a non-null value.
     *
     * @return bool True if filter fields are set, false otherwise.
     */
    public function hasFilterFields(): bool
    {
        return $this->filterFields !== null;
    }

    /**
     * Merges the provided filterFields collection with the current filterFields property.
     *
     * @param Collection $filterFields The collection of filterFields to merge with the current property.
     * @return void
     */
    public function mergeFilterFields(Collection $filterFields): void
    {
        $this->filterFields = CollectionPolicy::mergeNullable(
            $this->filterFields,
            $filterFields
        );
    }
}
