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

namespace PhalconKit\Provider\ModelsCache;

use PhalconKit\Provider\Cache\ServiceProvider as CacheServiceProvider;

/**
 * Registers the model-result cache service.
 *
 * The provider reuses the generic cache provider and exposes the resulting
 * cache wrapper under Phalcon's conventional `modelsCache` service name. Keep
 * model cache configuration aligned with the `cache` provider option shape.
 */
class ServiceProvider extends CacheServiceProvider
{
    protected string $serviceName = 'modelsCache';
}
