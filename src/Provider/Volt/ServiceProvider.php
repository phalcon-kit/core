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

namespace PhalconKit\Provider\Volt;

use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Di\DiInterface;
use Phalcon\Mvc\View\Engine\Volt;
use Phalcon\Mvc\ViewInterface;

/**
 * Registers the Volt template engine service.
 *
 * Volt is created with the configured view service and PhalconKit DI container,
 * then receives options from `volt` config. This keeps compiler paths, template
 * cache behavior, and engine options centralized in application configuration.
 *
 * @see https://docs.phalcon.io/5.13/volt/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'volt';
    
    /**
     * Register the shared `volt` service.
     *
     * @throws \PhalconKit\Exception\ServiceException When the `view` service
     *     does not implement Phalcon's view interface.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            
            $view = $di->getTyped('view', ViewInterface::class);
    
            $voltConfig = $config->pathToArray('volt') ?? [];
            
            $volt = new Volt($view, $di);
            $volt->setOptions($voltConfig);
            
            return $volt;
        });
    }
}
