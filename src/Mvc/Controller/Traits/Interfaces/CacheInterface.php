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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

/**
 * Contract for REST query cache-key helpers.
 */
interface CacheInterface
{
    /**
     * Build a cache key for the current query parameters.
     *
     * @param array<string, mixed>|null $params Optional request/query
     *     parameters. Implementations use current controller params when null.
     */
    public function getCacheKey(?array $params = null): ?string;
}
