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

use Phalcon\Filter\Exception as FilterException;
use Phalcon\Filter\Filter;
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractLimit;

/**
 * The Limit trait provides methods to handle query limits.
 */
trait Limit
{
    use AbstractLimit;
    
    use AbstractParams;
    
    protected ?int $limit = 10;
    protected ?int $maxLimit = 100;
    
    /**
     * Initializes the limit for the current instance.
     *
     * It sets the limit value to the result of calling the `getParam` method, passing
     * the 'limit' parameter as the first argument, an array of filters as the second argument,
     * and the result of calling the `defaultLimit` method as the third argument.
     *
     * @return void
     * @throws FilterException When Phalcon rejects the configured limit filter.
     * @throws HttpException When the resulting limit violates controller policy.
     */
    public function initializeLimit(): void
    {
        $this->setLimit($this->getParam('limit', [Filter::FILTER_ABSINT], $this->defaultLimit()));
    }
    
    /**
     * Sets the limit for the query.
     *
     * If the provided limit is less than -1, it throws an exception with an error message.
     * If the maximum limit is set and it's not -1, it checks if the provided limit is higher than the maximum limit, and throws an exception if it is.
     * After performing the necessary validations, it updates the limit property with the provided value.
     *
     * @param int|null $limit The limit to be set.
     * @return void
     * @throws HttpException If the provided limit is less than -1 or exceeds
     *     the maximum limit.
     */
    public function setLimit(?int $limit): void
    {
        if ($limit < -1) {
            throw new HttpException("Requested limit ({$limit}) must be higher or equal to -1", 400);
        }
        
        if (isset($this->maxLimit) && $this->maxLimit !== -1) {
            if ($limit > $this->maxLimit) {
                throw new HttpException("Requested limit ({$limit}) must be lower than the maximum limit ({$this->maxLimit})", 400);
            }
        }
        
        $this->limit = $limit;
    }
    
    /**
     * Returns the limit.
     *
     * If the limit is set to -1, then it returns null indicating that there is no limit,
     * else it returns the specified limit.
     *
     * @return int|null The limit value or null if there is no limit.
     */
    public function getLimit(): ?int
    {
        return $this->limit === -1 ? null : $this->limit;
    }
    
    /**
     * Sets the maximum limit.
     *
     * Sets the value of the maximum limit. If a value is provided,
     * it will be set as the new maximum limit. If a null value is provided,
     * the maximum limit will be unset.
     *
     * @param int|null $maxLimit The new maximum limit to be set.
     *
     * @return void
     */
    public function setMaxLimit(?int $maxLimit): void
    {
        $this->maxLimit = $maxLimit;
    }
    
    /**
     * Returns the maximum limit.
     *
     * If the maximum limit is set, then it returns that value,
     * else it returns null.
     *
     * @return int|null The maximum limit, or null if not set.
     */
    public function getMaxLimit(): ?int
    {
        return $this->maxLimit;
    }
    
    /**
     * Returns the default limit.
     *
     * If the limit is set, then it returns that value,
     * else it returns a default value of 100.
     *
     * @return int|null The default limit.
     */
    public function defaultLimit(): ?int
    {
        return $this->limit ?? 10;
    }
    
    /**
     * Returns the default maximum limit.
     *
     * If the maximum limit is set, then it returns that value,
     * else it returns a default value of 1000.
     *
     * @return int|null The default maximum limit.
     */
    public function defaultMaxLimit(): ?int
    {
        return $this->maxLimit ?? 100;
    }
}
