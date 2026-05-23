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

namespace PhalconKit\Provider\Annotations;

use Phalcon\Annotations\Adapter\Memory;
use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'annotations';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            // config
            $config = $di->getConfig();
            $annotationsConfig = $config->pathToArray('annotations', []);
    
            // options
            $driverName = $annotationsConfig['driver'] ?? 'memory';
            $driverOptions = $annotationsConfig['drivers'][$driverName] ?? [];
            $defaultOptions = $annotationsConfig['default'] ?? [];
            $options = array_merge($defaultOptions, $driverOptions);
    
            // adapter
            $adapter = $driverOptions['adapter'] ?? Memory::class;
            if (!is_string($adapter) || $adapter === '') {
                $adapter = Memory::class;
            }

            return new $adapter($options);
        });
    }
}
