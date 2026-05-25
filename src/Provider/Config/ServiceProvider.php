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

/**
 * Registers the framework configuration service.
 *
 * The provider reads the active bootstrap config, creates a default
 * `Bootstrap\Config` when none exists, and applies `app` config values to
 * `PhalconKit\Support\Php::set()`. This keeps PHP runtime flags synchronized
 * with application configuration during service bootstrap.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'config';
    
    /**
     * Optional provider-local config reference for custom subclasses.
     */
    protected ConfigInterface $config;
    
    /**
     * Register the shared `config` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When the bootstrap service
     *     is missing or does not implement the PhalconKit bootstrap contract.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
    
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
            $bootstrap->config ??= new Config();
            $config = $bootstrap->getConfig();
            
            Php::set($config->pathToArray('app') ?? []);
            
            return $config;
        });
    }
}
