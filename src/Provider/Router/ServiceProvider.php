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

namespace PhalconKit\Provider\Router;

use PhalconKit\Di\DiInterface;
use PhalconKit\Bootstrap;
use PhalconKit\Bootstrap\Router;
use PhalconKit\Cli\Router as CliRouter;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Ws\Router as WsRouter;

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'router';
    
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
            $config = $bootstrap->getConfig();
            
            $router = $bootstrap->router ?? match ($bootstrap->getMode()) {
                Bootstrap::MODE_CLI => new CliRouter(true),
                Bootstrap::MODE_WS => new WsRouter(true),
                Bootstrap::MODE_MVC => new Router(true, $config),
                default => throw new ConfigurationException(
                    'Unable to register router in bootstrap mode: `' . $bootstrap->getMode() . '`',
                    400
                ),
            };
            
            $configPath = match ($bootstrap->getMode()) {
                Bootstrap::MODE_CLI => 'router.cli',
                Bootstrap::MODE_WS => 'router.ws',
                default => 'router.defaults'
            };
            
            $defaults = $config->pathToArray($configPath) ?? [];
            $router->setDefaults($defaults);
            $router->setDI($di);
            
            if ($router instanceof Router) {
                $router->setEventsManager($di->get('eventsManager'));
                $router->setConfig($config);
                $router->baseRoutes();
                $router->hostnamesRoutes();
                $router->modulesRoutes($di->get('application'));
            }
            
            return $router;
        });
    }
}
