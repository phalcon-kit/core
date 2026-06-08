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

namespace PhalconKit\Provider\ModelsMetadata;

use Phalcon\Cache\AdapterFactory;
use PhalconKit\Di\DiInterface;
use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Mvc\Model\MetaData\Stream;
use Phalcon\Storage\SerializerFactory;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Php;

/**
 * Registers the model metadata service.
 *
 * Metadata adapter selection comes from `metadata.driver` for web runtime and
 * `metadata.driverCli` for CLI runtime. Adapter-specific options are merged
 * over `metadata.default`, mirroring the cache provider pattern while honoring
 * Phalcon's special constructor signatures for Memory and Stream metadata
 * adapters.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'modelsMetadata';
    
    /**
     * Register the shared `modelsMetadata` service.
     *
     * Built-in Memory and Stream adapters are instantiated directly. Other
     * adapters are created with Phalcon's cache adapter factory because their
     * constructors expect a storage backend.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            $config = $di->getConfig();
            
            $metadataConfig = $config->pathToArray('metadata') ?? [];
            
            $driverKey = Php::isCli() ? 'driverCli' : 'driver';
            $driverName = $metadataConfig[$driverKey] ?? 'memory';
            $driver = $metadataConfig['drivers'][$driverName] ?? [];
            $default = $metadataConfig['default'] ?? [];
            $options = array_merge($default, $driver);
            
            $adapter = $driver['adapter'] ?? Memory::class;
            if (!is_string($adapter) || $adapter === '') {
                $adapter = Memory::class;
            }

            if ($adapter === Memory::class) {
                return new Memory();
            }

            if ($adapter === Stream::class) {
                return new Stream($options);
            }

            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            return new $adapter($adapterFactory, $options);
        });
    }
}
