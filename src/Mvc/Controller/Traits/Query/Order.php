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

use Exception;
use Phalcon\Support\Collection;
use Phalcon\Filter\Filter;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractOrder;

/**
 * The Order trait sets and retrieves the order parameter for the query.
 */
trait Order
{
    use AbstractOrder;
    
    use AbstractModel;
    use AbstractParams;
    
    protected array|string|null $defaultOrder = null;
    protected ?Collection $order = null;
    
    /**
     * Initializes the default order for the instance.
     * @return void
     */
    public function initializeDefaultOrder(): void
    {
        $this->setDefaultOrder(null);
    }
    
    /**
     * Initializes the order parameter for the query.
     * This method processes and sets the order parameter based on the "order" input received.
     *
     * @return void
     * @throws Exception If the order parameter is invalid.
     */
    public function initializeOrder(): void
    {
        $this->initializeDefaultOrder();
        $order = $this->getParam('order', [Filter::FILTER_STRING, Filter::FILTER_TRIM], $this->getDefaultOrder());

        if (!isset($order)) {
            $this->setOrder(null);
            return;
        }
        
        if (is_string($order)) {
            $order = explode(',', $order);
        }
        
        // type check order parameter
        if (!is_array($order)) {
            throw new Exception(sprintf('Invalid type for "order" parameter: expected null, string, or array, got %s.', gettype($order)), 400);
        }

        $collection = new Collection([], false);
        foreach ($order as $key => $item) {
            if (is_int($key)) {
                if (is_string($item)) {
                    $item = preg_split('/\s+/', trim($item), -1, PREG_SPLIT_NO_EMPTY);
                }

                // skip empty results
                if (empty($item)) {
                    continue;
                }
                
                if (!is_array($item)) {
                    throw new Exception(sprintf('Invalid order element at index %d: expected string or array, got %s.', $key, gettype($item)), 400);
                }
                
                if (count($item) > 2) {
                    throw new Exception(sprintf('Invalid order element at index %d: expected [field, direction] with at most 2 elements, got %d.', $key, count($item)), 400);
                }

                // skip empty field name
                if (empty($item[0])) {
                    continue;
                }

                $collection->set($item[0], $this->appendModelName($item[0]) . ' ' . $this->getSide($item[1] ?? 'asc'));
            }
            // string
            else {
                $key  = trim($key);
                $item = trim((string) $item);

                // skip empty key
                if (empty($key)) {
                    continue;
                }

                $collection->set($key, $this->appendModelName($key) . ' ' . $this->getSide($item ?? 'asc'));
            }
        }

        $this->setOrder($collection);
    }
    
    /**
     * Sets the order for the query.
     * The provided order will replace any existing order previously set.
     *
     * @param Collection|null $order The order to be set. It can be a Collection object or null.
     * @return void
     */
    public function setOrder(?Collection $order): void
    {
        $this->order = $order;
    }
    
    /**
     * Retrieves the order assigned to the query.
     * If no order has been assigned, it will return null.
     *
     * @return Collection|null The order collection to assign to the query, or null if no order has been set.
     */
    public function getOrder(): ?Collection
    {
        return $this->order;
    }
    
    /**
     * Sets the default order for the query.
     * The default order will be used if no "order" parameter was provided
     *
     * @param array|string|null $defaultOrder The default order to be set. It can be an array, a string, or null.
     * @return void
     */
    public function setDefaultOrder(array|string|null $defaultOrder): void
    {
        $this->defaultOrder = $defaultOrder;
    }
    
    /**
     * Retrieves the default order for the query.
     *
     * @return array|string|null The default order. It can be an array, a string, or null.
     */
    public function getDefaultOrder(): array|string|null
    {
        return $this->defaultOrder;
    }
    
    /**
     * Returns the side value based on the given input.
     *
     * @param string $side The side value to be checked.
     *
     * @return string The side value. Returns 'desc' if the input is 'desc', otherwise returns 'asc'.
     */
    protected function getSide(string $side): string
    {
        if (strtolower(trim($side)) === 'desc') {
            return 'desc';
        }
        return 'asc';
    }
}
