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

namespace PhalconKit\Provider\Config;

use PhalconKit\Di\DiInterface;
use PhalconKit\Bootstrap;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Support\Php;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'config';
    
    protected ConfigInterface $config;
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        // Set shared service in DI
        $di->setShared($this->getName(), function () use ($di) {
    
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
            $bootstrap->config ??= new Config();
            $config = $bootstrap->getConfig();
            
            // Launch bootstrap prepare raw php configs
            Php::set($config->pathToArray('app') ?? []);
            
            // Set the config
            return $config;
        });
    }
}
