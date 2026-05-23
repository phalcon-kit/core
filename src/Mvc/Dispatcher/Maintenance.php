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

namespace PhalconKit\Mvc\Dispatcher;

use Phalcon\Dispatcher\AbstractDispatcher;
use Phalcon\Dispatcher\Exception as DispatcherException;
use Phalcon\Events\Event;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Di\Injectable;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Dispatcher;

/**
 * Maintenance Dispatcher Plugin
 * Redirect to the maintenance module/controller/action
 */
class Maintenance extends Injectable
{
    public const ?string DEFAULT_MAINTENANCE_MODULE = null;
    public const ?string DEFAULT_MAINTENANCE_CONTROLLER = 'error';
    public const ?string DEFAULT_MAINTENANCE_ACTION = 'maintenance';
    
    /**
     * Executed before dispatching a request.
     *
     * The plugin reads `app.maintenance` and `router.maintenance` from the
     * PhalconKit config service. When maintenance mode is enabled it forwards
     * the dispatcher to the configured maintenance route, strips null route
     * parts through the PhalconKit dispatcher extension when available, and
     * stops cancelable dispatch events so the original action is not executed.
     *
     * @param Event $event The event object.
     * @param AbstractDispatcher $dispatcher The dispatcher object.
     *
     * @return void
     *
     * @throws DispatcherException If an error happened during the dispatch
     *     forwarding to the maintenance route.
     * @throws ServiceException When the DI container or config service cannot
     *     be resolved through the PhalconKit DI contract.
     */
    public function beforeDispatch(Event $event, AbstractDispatcher $dispatcher): void
    {
        $config = ServiceResolver::fromContainer(
            $this->getDI(),
            'config',
            ConfigInterface::class,
            context: 'maintenance dispatcher plugin'
        );
        
        $maintenance = $config->path('app.maintenance', false);
        if ($maintenance) {
            $route = $config->pathToArray('router.maintenance') ?? [];
            $route['module'] ??= self::DEFAULT_MAINTENANCE_MODULE;
            $route['controller'] ??= self::DEFAULT_MAINTENANCE_CONTROLLER;
            $route['action'] ??= self::DEFAULT_MAINTENANCE_ACTION;
            
            if ($dispatcher instanceof Dispatcher) {
                $dispatcher->forward($route, true);
            } else {
                $dispatcher->forward($route);
            }
            
            if ($event->isCancelable()) {
                $event->stop();
            }
        }
    }
}
