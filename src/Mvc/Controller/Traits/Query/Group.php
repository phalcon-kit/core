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

use Phalcon\Filter\Exception;
use Phalcon\Filter\Filter;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractGroup;
use PhalconKit\Support\CollectionPolicy;

trait Group
{
    use AbstractGroup;
    
    use AbstractParams;
    use AbstractModel;
    
    protected ?Collection $group = null;

    /**
     * Initializes the group by retrieving and processing input parameters.
     *
     * This method retrieves the 'group' parameter, applies filters, and formats it as a collection.
     * If the parameter is not set, the group is set to null. Otherwise, the parameter is split
     * into an array (if not already an array) and processed into a `Collection` where keys and values
     * are appropriately trimmed and adjusted.
     *
     * @return void This method does not return a value but updates the group's state internally.
     * @throws Exception
     */
    public function initializeGroup(): void
    {
        $group = $this->getParam('group', [
            Filter::FILTER_STRING,
            Filter::FILTER_TRIM
        ], $this->defaultGroup() ?? null);
        
        if (!isset($group)) {
            $this->setGroup(null);
        }
        
        if (!is_array($group)) {
            $group = explode(',', $group);
        }
        
        $collection = new Collection([], false);
        foreach ($group as $key => $item) {
            $collection->set(
                is_int($key)? $item : trim($key),
                trim($this->appendModelName($item))
            );
        }
        $this->setGroup($collection);
    }

    /**
     * Retrieves the current group collection.
     *
     * @return Collection|null The current group collection or null if not set.
     */
    public function getGroup(): ?Collection
    {
        return $this->group;
    }

    /**
     * Sets the group collection.
     *
     * @param Collection|null $group The group collection to be set. Can be null.
     * @return void
     */
    public function setGroup(?Collection $group): void
    {
        $this->group = $group;
    }

    /**
     * Merges the provided group collection with the current group property.
     *
     * @param Collection $group The collection of group to merge with the current property.
     * @return void
     */
    public function mergeGroup(Collection $group): void
    {
        $this->group = CollectionPolicy::mergeNullable(
            $this->group,
            $group
        );
    }

    /**
     * Retrieves the default group based on defined joins.
     *
     * @return array|string|null The primary key attributes as an array or string if joins are defined and have elements; otherwise, null.
     */
    public function defaultGroup(): array|string|null
    {
        return (isset($this->joins) && count($this->joins) > 0) ?
            $this->getPrimaryKeyAttributes()
            : null;
    }
}
