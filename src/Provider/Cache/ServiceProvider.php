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

namespace PhalconKit\Provider\Cache;

use PhalconKit\Di\DiInterface;
use PhalconKit\Cache\Cache;
use Phalcon\Cache\AdapterFactory;
use Phalcon\Storage\SerializerFactory;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Php;

/**
 * Registers the application cache service.
 *
 * The provider builds a Phalcon cache adapter from `cache.driver` in web
 * runtime and from `cache.cli` in CLI runtime, then wraps it in
 * `PhalconKit\Cache\Cache`. Driver-specific options are merged over
 * `cache.default` so shared defaults remain central while each adapter can
 * override only what it needs.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'cache';
    
    /**
     * Register the shared `cache` service.
     *
     * Adapter creation is delegated to Phalcon's cache adapter factory, so
     * driver names and option arrays should follow Phalcon's cache adapter
     * expectations.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $cacheConfig = $config->pathToArray('cache') ?? [];
            
            $driverNameKey = Php::isCli() ? 'cli' : 'driver';
            $driverName = $cacheConfig[$driverNameKey] ?? 'memory';
            $driverOptions = $cacheConfig['drivers'][$driverName] ?? [];
            $defaultOptions = $cacheConfig['default'] ?? [];
            $options = array_merge($defaultOptions, $driverOptions);
            
            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            $adapter = $adapterFactory->newInstance($driverName, $options);
            
            return new Cache($adapter);
        });
    }
}
