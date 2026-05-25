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

namespace PhalconKit\Provider\Dispatcher;

use PhalconKit\Di\DiInterface;
use PhalconKit\Bootstrap;
use PhalconKit\Cli\Dispatcher as CliDispatcher;
use PhalconKit\Ws\Dispatcher as WsDispatcher;
use PhalconKit\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Mvc\Dispatcher\Preflight;
use PhalconKit\Mvc\Dispatcher\Error;
use PhalconKit\Mvc\Dispatcher\Rest;
use PhalconKit\Mvc\Dispatcher\Security;
use PhalconKit\Mvc\Dispatcher\Maintenance;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the mode-specific dispatcher and core dispatch listeners.
 *
 * The dispatcher service is selected from the active bootstrap mode: MVC
 * receives the HTTP dispatcher, CLI receives the task dispatcher, and WebSocket
 * receives the task-style WebSocket dispatcher. Shared listeners such as
 * preflight, ACL security, maintenance checks, logging, and module bootstrapping
 * are attached before the concrete dispatcher is returned.
 *
 * MVC-only listeners are attached only for HTTP dispatching. CLI and WebSocket
 * dispatchers keep the common listeners but avoid MVC error/rest behavior that
 * depends on controllers and HTTP responses.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'dispatcher';
    
    /**
     * Register the shared `dispatcher` service.
     *
     * The returned dispatcher is configured with the shared events manager, the
     * PhalconKit DI container, and the default namespace from
     * `router.defaults.namespace` when that config value exists.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $eventsManager = $di->get('eventsManager');
            
            $config = $di->getConfig();
            
            $bootstrap = $di->getTyped('bootstrap', Bootstrap::class);
            
            /**
             * CORS & Preflight
             */
            $security = new Preflight();
            $security->setDI($di);
            $eventsManager->attach('dispatch', $security);
            
            /**
             * Security
             */
            $security = new Security();
            $security->setDI($di);
            $eventsManager->attach('dispatch', $security);
            
            /**
             * Maintenance
             */
            $maintenance = new Maintenance();
            $maintenance->setDI($di);
            $eventsManager->attach('dispatch', $maintenance);
            
            /**
             * Logger
             */
            $logger = new MvcDispatcher\Logger();
            $logger->setDI($di);
            $eventsManager->attach('dispatch', $logger);
            
            /**
             * Module
             */
            $module = new MvcDispatcher\Module();
            $module->setDI($di);
            $eventsManager->attach('dispatch', $module);
            
            /**
             * CLI Dispatcher
             */
            if ($bootstrap->isCli()) {
                $dispatcher = new CliDispatcher();
            }
            
            elseif ($bootstrap->isWs()) {
                $dispatcher = new WsDispatcher();
            }
            
            /**
             * MVC Dispatcher
             */
            else {
                /**
                 * Error
                 */
                $error = new Error();
                $error->setDI($di);
                $eventsManager->attach('dispatch', $error);
                
                /**
                 * Rest
                 */
                $rest = new Rest();
                $rest->setDI($di);
                $eventsManager->attach('dispatch', $rest);
                
                // MVC Dispatcher
                $dispatcher = new MvcDispatcher();
            }
            
            $dispatcher->setEventsManager($eventsManager);
            $dispatcher->setDI($di);
    
            // Set default namespace
            $routerDefaultNamespace = $config->path('router.defaults.namespace');
            if (!empty($routerDefaultNamespace)) {
                $dispatcher->setDefaultNamespace($routerDefaultNamespace);
            }
            
            return $dispatcher;
        });
    }
}
