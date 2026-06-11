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
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractColumn;
use PhalconKit\Support\CollectionPolicy;

trait Column
{
    use AbstractColumn;

    protected ?Collection $column = null;

    /**
     * Initializes the column by setting it to null.
     *
     * @return void
     */
    public function initializeColumn(): void
    {
        $this->setColumn(null);
    }

    /**
     * Sets the column collection.
     *
     * @param array|Collection|null $column The collection to set, or null to unset the column collection.
     * @return void
     */
    public function setColumn(array|Collection|null $column): void
    {
        $this->column = CollectionPolicy::normalizeNullable($column);
    }

    /**
     * Retrieves the column collection.
     *
     * @return Collection|null Returns a collection of column collection, or null if no collection is available.
     */
    public function getColumn(): ?Collection
    {
        return $this->column;
    }

    /**
     * Merges the provided column collection with the current column property.
     *
     * @param array|Collection $column The collection of columns to merge with the current property.
     * @return void
     */
    public function mergeColumn(array|Collection $column): void
    {
        $this->column = CollectionPolicy::mergeNullable(
            $this->column,
            CollectionPolicy::normalize($column)
        );
    }
}
