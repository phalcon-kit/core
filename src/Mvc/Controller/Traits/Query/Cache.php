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
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractInjectable;
use PhalconKit\Mvc\Controller\Traits\Abstracts\AbstractParams;
use PhalconKit\Mvc\Controller\Traits\Abstracts\Query\AbstractCache;
use PhalconKit\Support\CollectionPolicy;

/**
 * This trait provides methods for caching data for the query.
 */
trait Cache
{
    use AbstractCache;
    
    use AbstractInjectable;
    use AbstractParams;
    
    /**
     * This variable holds the configuration settings for caching.
     * @var Collection|null $cacheConfig
     */
    public ?Collection $cacheConfig = null;
    
    /**
     * The cache key used for storing data in the cache.
     * @var string|null
     */
    public ?string $cacheKey = null;
    
    /**
     * The lifetime of the cache data in seconds.
     * @var int|null
     */
    public ?int $cacheLifetime = null;
    
    /**
     * Initializes the cache.
     *
     * This method initializes the cache by setting the cache key and lifetime.
     *
     * @return void
     * @throws Exception
     */
    public function initializeCacheConfig(): void
    {
        $this->initializeCacheLifetime();
        $this->initializeCacheKey();
        
        $lifetime = $this->getCacheLifetime();
        $key = $this->getCacheKey();
        
        $this->setCacheConfig(new Collection(isset($lifetime, $key) ? [
            'lifetime' => $this->getCacheLifetime(),
            'key' => $this->getCacheKey()
        ] : []));
    }
    
    /**
     * Initializes the cache lifetime.
     *
     * This method retrieves the 'lifetime' parameter using `getParam()` method,
     * applies the 'FILTER_ABSINT' filter to it, and then sets the cache lifetime
     * using `setCacheLifetime()` method with the filtered value.
     *
     * @return void
     * @throws Exception
     */
    public function initializeCacheLifetime(): void
    {
        $lifetime = $this->getParam('lifetime', [Filter::FILTER_ABSINT]);
        $this->setCacheLifetime($lifetime);
    }
    
    /**
     * Initializes the cache key.
     *
     * This method constructs and sets a unique cache key by combining the model name,
     * user identity, cache lifetime, and parameters.
     *
     * @return void
     */
    public function initializeCacheKey(): void
    {
        $cacheKeys = array_filter([
            $this->getModelName(),
            $this->getCacheLifetime(),
            $this->identity->getUserId(),
        ]);
        
        $jsonParams = json_encode($this->getParams(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!empty($jsonParams)) {
            $cacheKeys[] = hash('xxh3', $jsonParams);
        }
        
        $this->setCacheKey('_' . implode('-', $cacheKeys) . '_');
    }
    
    /**
     * Sets the cache lifetime.
     *
     * @param int|null $cacheLifetime The cache lifetime.
     * @return void
     */
    public function setCacheLifetime(?int $cacheLifetime): void
    {
        $this->cacheLifetime = $cacheLifetime;
    }
    
    /**
     * Retrieves the cache lifetime.
     *
     * @return int|null The cache lifetime.
     */
    public function getCacheLifetime(): ?int
    {
        return $this->cacheLifetime;
    }
    
    /**
     * Sets the cache key.
     *
     * @param string|null $cacheKey The cache key.
     * @return void
     */
    public function setCacheKey(?string $cacheKey): void
    {
        $this->cacheKey = $cacheKey;
    }
    
    /**
     * Retrieves the cache key.
     *
     * @return string|null The cache key.
     */
    public function getCacheKey(): ?string
    {
        return $this->cacheKey;
    }
    
    /**
     * Set the cache config collection for the query.
     *
     * @param Collection|null $cacheConfig The cache config collection, or null to disable.
     * @return void
     */
    public function setCacheConfig(?Collection $cacheConfig): void
    {
        $this->cacheConfig = $cacheConfig;
    }
    
    /**
     * Retrieves the cache collection for the query.
     *
     * @return Collection|null The cache config collection, or null if no cache is set.
     */
    public function getCacheConfig(): ?Collection
    {
        return $this->cacheConfig;
    }

    /**
     * Merges the provided cacheConfig collection with the current cacheConfig property.
     *
     * @param Collection $cacheConfig The collection of cacheConfig fields to merge with the current property.
     * @return void
     */
    public function mergeCacheConfig(Collection $cacheConfig): void
    {
        $this->cacheConfig = CollectionPolicy::mergeNullable(
            $this->cacheConfig,
            $cacheConfig
        );
    }
}
