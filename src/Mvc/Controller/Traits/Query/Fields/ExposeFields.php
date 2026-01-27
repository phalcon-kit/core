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

trait ExposeFields
{
    protected ?Collection $exposeFields = null;
    
    /**
     * Initializes the expose fields.
     *
     * This method is responsible for initializing the necessary expose fields for the model
     *
     * @return void
     */
    public function initializeExposeFields(): void
    {
        $this->setExposeFields(null);
    }
    
    /**
     * Sets the fields for exposing data.
     *
     * @param Collection|null $exposeFields The array of expose fields.
     *                                      Pass null to allow exposing all fields.
     */
    public function setExposeFields(?Collection $exposeFields): void
    {
        $this->exposeFields = $exposeFields;
    }
    
    /**
     * Returns the expose fields.
     *
     * This method retrieves the expose fields for the model.
     * If expose fields have been set, it returns the collection of expose fields.
     * If no expose fields have been set, it returns null.
     *
     * Note: The expose fields are the fields that are exposed with the response.
     *
     * @return Collection|null The collection of expose fields or null if no expose fields have been set.
     */
    public function getExposeFields(): ?Collection
    {
        return $this->exposeFields;
    }

    /**
     * Determines if the exposeFields property is set to a non-null value.
     *
     * @return bool True if exposeFields is not null, false otherwise.
     */
    public function hasExposeFields(): bool
    {
        return $this->exposeFields !== null;
    }

    /**
     * Merges the provided exposeFields collection with the current exposeFields property.
     *
     * @param Collection $exposeFields The collection of exposeFields to merge with the current property.
     * @return void
     */
    public function mergeExposeFields(Collection $exposeFields): void
    {
        $this->exposeFields = CollectionPolicy::mergeNullable(
            $this->exposeFields,
            $exposeFields
        );
    }
}
