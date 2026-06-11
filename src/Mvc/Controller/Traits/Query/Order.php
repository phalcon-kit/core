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
use Phalcon\Filter\Filter;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractModel;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractOrder;
use PhalconKit\Mvc\Controller\Traits\Query\Fields\OrderFields;
use PhalconKit\Support\CollectionPolicy;

/**
 * Parses REST `order` parameters into Phalcon-compatible query expressions.
 *
 * By default the parser preserves the historical PhalconKit behavior and
 * accepts any field name that passes identifier normalization. Controllers can
 * opt in to explicit order-field allow-lists through `initializeOrderFields()`
 * / `setOrderFields()`, usually provided by the aggregate Query field policy
 * initialization.
 */
trait Order
{
    use AbstractOrder;
    use OrderFields;
    
    use AbstractModel;
    use AbstractParams;
    
    /**
     * Controller-owned fallback order used when the request has no `order`.
     */
    protected array|string|null $defaultOrder = null;

    /**
     * Parsed ORDER BY expressions keyed by public field name.
     */
    protected ?Collection $order = null;
    
    /**
     * Initialize the default order used by the REST query.
     *
     * Concrete controllers can override this method and call
     * {@see setDefaultOrder()} when a resource should always use a stable sort
     * unless the client provides one. The default remains null so existing
     * controllers keep Phalcon's natural model ordering.
     */
    public function initializeDefaultOrder(): void
    {
        $this->setDefaultOrder(null);
    }
    
    /**
     * Parse the request `order` parameter into model-qualified expressions.
     *
     * Accepted request forms:
     * - `?order=title desc,createdAt asc`
     * - `order[title]=desc`
     * - `order[]=title desc`
     * - `order[][0]=title&order[][1]=desc`
     *
     * Direction handling is intentionally small and deterministic: only `desc`
     * is treated as descending, every other value falls back to ascending. When
     * an order-field policy is configured, the public field name must resolve
     * through that policy before it is formatted for PHQL.
     *
     * @throws HttpException When the root value, an element shape, or a
     *     restricted field is invalid for REST query ordering.
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
            throw new HttpException(sprintf('Invalid type for "order" parameter: expected null, string, or array, got %s.', gettype($order)), 400);
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
                    throw new HttpException(sprintf('Invalid order element at index %d: expected string or array, got %s.', $key, gettype($item)), 400);
                }
                
                if (count($item) > 2) {
                    throw new HttpException(sprintf('Invalid order element at index %d: expected [field, direction] with at most 2 elements, got %d.', $key, count($item)), 400);
                }

                $field = is_scalar($item[0] ?? null) ? trim((string)$item[0]) : '';

                // skip empty field name
                if ($field === '') {
                    continue;
                }

                $queryField = $this->resolveOrderField($field);
                $collection->set(
                    $field,
                    $this->appendModelName($queryField) . ' ' . $this->getSide((string)($item[1] ?? 'asc'))
                );
            }
            // string
            else {
                $key  = trim($key);
                $item = trim((string) $item);

                // skip empty key
                if (empty($key)) {
                    continue;
                }

                $queryField = $this->resolveOrderField($key);
                $collection->set($key, $this->appendModelName($queryField) . ' ' . $this->getSide($item));
            }
        }

        $this->setOrder($collection);
    }
    
    /**
     * Replace the parsed order collection for the query.
     *
     * Values are compiled later by {@see \PhalconKit\Mvc\Controller\Traits\Query::prepareFind()}.
     * Use null when no ORDER BY clause should be sent to Phalcon.
     */
    public function setOrder(array|Collection|null $order): void
    {
        $this->order = CollectionPolicy::normalizeNullable($order);
    }
    
    /**
     * Return the parsed order collection, or null when no ordering is active.
     *
     * Keys are public REST field names. Values are PHQL-ready field expressions
     * with normalized direction suffixes.
     */
    public function getOrder(): ?Collection
    {
        return $this->order;
    }
    
    /**
     * Replace the default order used when the request has no `order` parameter.
     *
     * The value accepts the same shapes as the public `order` parameter so
     * controller-owned defaults and request-supplied order definitions compile
     * through the same path.
     */
    public function setDefaultOrder(array|string|null $defaultOrder): void
    {
        $this->defaultOrder = $defaultOrder;
    }
    
    /**
     * Return the default order definition for the current request.
     *
     * A null return value means no default order will be applied.
     */
    public function getDefaultOrder(): array|string|null
    {
        return $this->defaultOrder;
    }
    
    /**
     * Resolve a public order field to the query field used in PHQL.
     *
     * Null order fields preserve legacy unrestricted ordering. Once a policy is
     * configured, only public names in the normalized field map are accepted.
     *
     * @throws HttpException When the field is not enabled by the configured
     *     order-field policy.
     */
    protected function resolveOrderField(string $field): string
    {
        if ($this->getOrderFields() === null) {
            return $field;
        }

        $orderFields = $this->getOrderFieldMap();
        if (array_key_exists($field, $orderFields)) {
            return $orderFields[$field];
        }

        throw new HttpException(sprintf('Unauthorized order field "%s".', $field), 403);
    }

    /**
     * Normalize the requested order direction.
     *
     * REST ordering accepts only one explicit descending token. Unknown,
     * omitted, or empty values intentionally fall back to ascending so malformed
     * directions do not become SQL fragments.
     */
    protected function getSide(string $side): string
    {
        if (strtolower(trim($side)) === 'desc') {
            return 'desc';
        }
        return 'asc';
    }
}
