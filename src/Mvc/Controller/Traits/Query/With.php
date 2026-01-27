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

namespace PhalconKit\Mvc\Controller\Traits\Query;

use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractWith;
use PhalconKit\Support\CollectionPolicy;

trait With
{
    use AbstractWith;
    
    protected ?Collection $with = null;
    
    /**
     * Initializes the relationships.
     *
     * This method is responsible for initializing the allowed relationship aliases.
     *
     * @return void
     */
    public function initializeWith(): void
    {
        $this->setWith(null);
    }
    
    /**
     * Sets the allowed relationship aliases.
     *
     * @param Collection|null $with The collection of relationship aliases.
     *                              Pass null to allow any relationships.
     */
    public function setWith(?Collection $with): void
    {
        $this->with = $with;
    }
    
    /**
     * Returns the relationship aliases collection.
     *
     * This method retrieves the allowed relationship aliases for the model.
     * If with fields have been set, it returns the collection of relationship aliases.
     * If no relationship aliases have been set, it returns null.
     *
     * Note: The relationship aliases are the fields that are allowed to retrieve during queries.
     *
     * @return Collection|null The collection of relationship aliases or null everything is allowed.
     */
    public function getWith(): ?Collection
    {
        return $this->with;
    }

    /**
     * Merges the provided "with" collection with the existing "with" in the find criteria.
     *
     * @param Collection $with The collection of "with" to be merged.
     */
    public function mergeWith(Collection $with): void
    {
        $this->with = CollectionPolicy::mergeNullable(
            $this->with,
            $with
        );
    }
}
