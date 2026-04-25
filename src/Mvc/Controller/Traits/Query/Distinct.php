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

use Phalcon\Filter\Filter;
use Phalcon\Support\Collection;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractDistinct;
use PhalconKit\Support\CollectionPolicy;

trait Distinct
{
    use AbstractDistinct;
    use AbstractParams;
    
    protected ?Collection $distinct = null;
    
    /**
     * Initialize the distinct parameter for the query.
     *
     * @return void
     * @throws \Phalcon\Filter\Exception If an error occurs during filtering
     */
    public function initializeDistinct(): void
    {
        $distinct = $this->getParam('distinct', [Filter::FILTER_STRING, Filter::FILTER_TRIM]);
        
        if (!isset($distinct)) {
            $this->setDistinct(null);
            return;
        }
        
        if (!is_array($distinct)) {
            $distinct = explode(',', $distinct);
        }
        
        foreach ($distinct as $key => $item) {
            if (is_int($key)) {
                $distinct[trim($item)] = true;
            }
            unset($distinct[$key]);
        }
        
        $this->setDistinct(new Collection($distinct, false));
    }
    
    /**
     * Sets the distinct collection.
     *
     * @param Collection|null $distinct The distinct collection to set.
     *
     * @return void
     */
    public function setDistinct(?Collection $distinct): void
    {
        $this->distinct = $distinct;
    }
    
    /**
     * Gets the distinct collection.
     *
     * @return Collection|null The distinct collection, if set; otherwise, null.
     */
    public function getDistinct(): ?Collection
    {
        return $this->distinct;
    }

    /**
     * Merges the provided distinct collection with the current distinct property.
     *
     * @param Collection $distinct The collection of distinct to merge with the current property.
     * @return void
     */
    public function mergeDistinct(Collection $distinct): void
    {
        $this->distinct = CollectionPolicy::mergeNullable(
            $this->distinct,
            $distinct
        );
    }
}
