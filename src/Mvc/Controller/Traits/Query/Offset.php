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
use PhalconKit\Exception\HttpException;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractOffset;

/**
 * This trait provides functionality to set and get an offset value for a query.
 */
trait Offset
{
    use AbstractOffset;
    
    use AbstractParams;
    
    protected ?int $offset = 0;
    
    /**
     * Initializes the offset from the request, or from `defaultOffset()`.
     *
     * A missing, null, or empty-string `offset` parameter uses
     * `defaultOffset()`. A provided value must be a non-negative integer (an
     * int or a decimal digit string such as `"20"`); anything else is rejected
     * with HTTP 400 instead of being coerced.
     *
     * @return void
     * @throws FilterException When Phalcon rejects parameter filtering.
     * @throws HttpException When the requested offset is not a non-negative
     *     integer.
     */
    public function initializeOffset(): void
    {
        $offset = $this->getParam('offset');

        if ($offset === null || $offset === '') {
            $this->setOffset($this->defaultOffset());
            return;
        }

        $parsed = is_int($offset) || is_string($offset)
            ? filter_var($offset, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])
            : false;

        if (!is_int($parsed)) {
            throw new HttpException('Requested offset must be a non-negative integer', 400);
        }

        $this->setOffset($parsed);
    }
    
    /**
     * Sets the offset value for the query.
     *
     * @param int|null $offset The offset value to set for the query. Specify an integer representing the offset value or null if no offset is required.
     * @return void
     * @throws HttpException If the specified offset value is less than 0.
     */
    public function setOffset(?int $offset): void
    {
        if ($offset < 0) {
            throw new HttpException("Query offset ({$offset}) must be higher than or equal to 0", 400);
        }
        
        $this->offset = $offset;
    }
    
    /**
     * Returns the offset value.
     *
     * @return int|null The offset value. Returns either an integer representing the offset value or null if no offset is set.
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }
    
    /**
     * Returns the default offset value.
     *
     * @return int|null The default offset value. Returns either an integer representing the offset value or null if no offset is set.
     */
    public function defaultOffset(): ?int
    {
        return 0;
    }
}
