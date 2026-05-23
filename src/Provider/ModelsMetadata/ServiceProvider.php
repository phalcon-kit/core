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
use PhalconKit\Bootstrap;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Php;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'modelsMetadata';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
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

            if (in_array($adapter, [Memory::class, Stream::class])) {
                return new $adapter($options);
            }

            $serializerFactory = new SerializerFactory();
            $adapterFactory = new AdapterFactory($serializerFactory);
            return new $adapter($adapterFactory, $options);
        });
    }
}
