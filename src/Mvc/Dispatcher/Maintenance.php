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
use Phalcon\Cli\Dispatcher as CliDispatcher;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use Phalcon\Events\Event;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Di\Injectable;
use PhalconKit\Dispatcher\DispatcherInterface;
use PhalconKit\Exception\ServiceException;

/**
 * Dispatcher listener that redirects traffic while maintenance mode is enabled.
 *
 * The listener reads `app.maintenance` and `router.maintenance` from config and
 * forwards matching requests before the target controller/action runs.
 */
class Maintenance extends Injectable
{
    /**
     * Default maintenance module route part.
     */
    public const ?string DEFAULT_MAINTENANCE_MODULE = null;

    /**
     * Default maintenance controller route part.
     */
    public const ?string DEFAULT_MAINTENANCE_CONTROLLER = 'error';

    /**
     * Default maintenance action route part.
     */
    public const ?string DEFAULT_MAINTENANCE_ACTION = 'maintenance';
    
    /**
     * Executed before dispatching a request.
     *
     * The plugin reads `app.maintenance` and `router.maintenance` from the
     * PhalconKit config service. When maintenance mode is enabled it forwards
     * the dispatcher to the configured maintenance route, strips null route
     * parts through the PhalconKit dispatcher extension when available, and
     * stops cancelable dispatch events only while rerouting. The effective
     * maintenance target proceeds normally, including subsequent listeners.
     * Empty namespace/handler/action names resolve to dispatcher defaults;
     * omitted and null parts retain the dispatcher's forwarding semantics.
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

            $canForward = $dispatcher instanceof DispatcherInterface
                ? $dispatcher->canForward($route)
                : $this->canForwardNative($dispatcher, $route);
            if (!$canForward) {
                return;
            }
            
            $dispatcher->forward($route);

            if ($event->isCancelable()) {
                $event->stop();
            }
        }
    }

    /**
     * Compatibility check for native dispatchers without Core's canForward().
     *
     * @param array<string, mixed> $route Configured route, including null parts.
     */
    private function canForwardNative(AbstractDispatcher $dispatcher, array $route): bool
    {
        // Phalcon exposes no default action/handler getters. Resolve them on
        // a copy so inspecting empty target parts cannot alter the live route.
        $defaultDispatcher = clone $dispatcher;
        $defaultDispatcher->setActionName('');
        if ($defaultDispatcher instanceof MvcDispatcher) {
            $defaultDispatcher->setControllerName('');
        } elseif ($defaultDispatcher instanceof CliDispatcher) {
            $defaultDispatcher->setTaskName('');
        }
        $defaultDispatcher->getHandlerClass();

        $current = [
            'namespace' => $dispatcher->getNamespaceName(),
            'action' => $dispatcher->getActionName(),
            'params' => $dispatcher->getParameters(),
        ];
        $defaults = [
            'namespace' => $dispatcher->getDefaultNamespace(),
            'action' => $defaultDispatcher->getActionName(),
        ];
        if ($dispatcher instanceof MvcDispatcher && $defaultDispatcher instanceof MvcDispatcher) {
            $current['controller'] = $dispatcher->getControllerName();
            $defaults['controller'] = $defaultDispatcher->getControllerName();
        } elseif ($dispatcher instanceof CliDispatcher && $defaultDispatcher instanceof CliDispatcher) {
            // Native forward() gives controller precedence over task, also in CLI.
            $route['task'] = $route['controller'] ?? $route['task'] ?? null;
            unset($route['controller']);
            $current['task'] = $dispatcher->getTaskName();
            $defaults['task'] = $defaultDispatcher->getTaskName();
        }

        foreach ($defaults as $part => $default) {
            if (isset($route[$part]) && !$route[$part]) {
                $route[$part] = $default;
            }
        }

        // Native forward() does not update the module; Core's extension does.
        return array_any($current, static fn(mixed $value, string $part): bool => array_key_exists($part, $route) && $route[$part] !== $value);
    }
}
