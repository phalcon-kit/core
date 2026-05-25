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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query;

use Phalcon\Support\Collection;

/**
 * Abstract contract for query result cache options.
 */
trait AbstractCache
{
    /**
     * Initialize the full cache option collection.
     */
    abstract public function initializeCacheConfig(): void;
    
    /**
     * Initialize the cache key for the current query.
     */
    abstract public function initializeCacheKey(): void;

    /**
     * Initialize the cache lifetime for the current query.
     */
    abstract public function initializeCacheLifetime(): void;
    
    /**
     * Replace the computed cache key.
     */
    abstract public function setCacheKey(?string $cacheKey): void;
    
    /**
     * Return the computed cache key.
     */
    abstract public function getCacheKey(): ?string;
    
    /**
     * Replace the cache lifetime, in seconds.
     */
    abstract public function setCacheLifetime(?int $cacheLifetime): void;
    
    /**
     * Return the cache lifetime, in seconds.
     */
    abstract public function getCacheLifetime(): ?int;
    
    /**
     * Replace the Phalcon `cache` find-option collection.
     */
    abstract public function setCacheConfig(?Collection $cacheConfig): void;
    
    /**
     * Return the Phalcon `cache` find-option collection.
     */
    abstract public function getCacheConfig(): ?Collection;
}
