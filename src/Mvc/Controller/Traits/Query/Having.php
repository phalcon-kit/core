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
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractHaving;
use PhalconKit\Support\CollectionPolicy;

trait Having
{
    use AbstractHaving;
    
    protected ?Collection $having = null;

    /**
     * Initializes the having property to its default state.
     *
     * @return void
     */
    public function initializeHaving(): void
    {
        $this->setHaving(null);
    }

    /**
     * Retrieves the current having collection.
     *
     * @return Collection|null The collection of having, or null if none is set.
     */
    public function getHaving(): ?Collection
    {
        return $this->having;
    }

    /**
     * Sets the having property to the provided collection.
     *
     * @param Collection|null $having The collection to set as the having property, or null to clear it.
     * @return void
     */
    public function setHaving(?Collection $having): void
    {
        $this->having = $having;
    }

    /**
     * Merges the provided having collection with the current having property.
     *
     * @param Collection $having The collection of having to merge with the current property.
     * @return void
     */
    public function mergeHaving(Collection $having): void
    {
        $this->having = CollectionPolicy::mergeNullable(
            $this->having,
            $having
        );
    }
}
