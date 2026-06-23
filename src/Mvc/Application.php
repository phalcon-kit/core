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

namespace PhalconKit\Mvc;

use Phalcon\Di\DiInterface as NativeDiInterface;
use Phalcon\Http\ResponseInterface;
use Phalcon\Dispatcher\AbstractDispatcher;
use PhalconKit\Cli\Dispatcher as CliDispatcher;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Dispatcher as MvcDispatcher;

/**
 * MVC application with PhalconKit's typed DI boundary and HMVC helper.
 *
 * The application keeps Phalcon's MVC lifecycle, but it requires a PhalconKit
 * DI implementation so internal framework code can rely on typed service
 * helpers. `request()` provides a small HMVC dispatch helper for rendering an
 * internal controller/task target without mutating the active dispatcher.
 *
 * @see https://docs.phalcon.io/5.16/application/
 */
class Application extends \Phalcon\Mvc\Application
{
    /**
     * Creates an HMVC application bound to a PhalconKit DI container.
     *
     * The constructor keeps Phalcon's native DI signature so the application
     * remains compatible with inherited Phalcon APIs, but the provided
     * container must implement PhalconKit's DI contract. This guarantees
     * runtime code can use typed service lookups and fail with framework
     * exceptions when a service is missing or misconfigured.
     *
     * @param NativeDiInterface $di Container used to resolve application
     *     services.
     * @throws ServiceException When the container does not expose PhalconKit
     *     typed DI helpers.
     */
    public function __construct(NativeDiInterface $di)
    {
        $di = ServiceResolver::requirePhalconKitContainer(
            $di,
            'create PhalconKit MVC application',
            'the application DI'
        );

        // Registering app itself as a service
        $di->setShared('application', $this);
        parent::__construct($di);
    }

    /**
     * Assigns the application DI container.
     *
     * Phalcon exposes this setter through its injection-aware base class. The
     * override keeps that public extension point available while enforcing that
     * replacement containers still implement PhalconKit's typed DI contract.
     *
     * @param NativeDiInterface $container Replacement application container.
     * @throws ServiceException When the container does not expose PhalconKit
     *     typed DI helpers.
     */
    #[\Override]
    public function setDI(NativeDiInterface $container): void
    {
        $container = ServiceResolver::requirePhalconKitContainer(
            $container,
            'assign PhalconKit MVC application DI',
            'the replacement DI'
        );
        parent::setDI($container);
    }
    
    /**
     * Dispatches an internal HMVC location and returns its rendered content.
     *
     * The current `dispatcher` DI service is cloned before routing state is
     * applied, so nested HMVC requests do not mutate the application's main
     * dispatcher. MVC dispatchers receive a controller name, CLI dispatchers
     * receive a task name, and all dispatchers receive namespace, module,
     * action, and params from the location array.
     *
     * @param array{
     *     namespace?: string,
     *     module?: string,
     *     controller?: string,
     *     task?: string,
     *     action?: string,
     *     params?: array<mixed>
     * } $location Optional namespace/module/controller/task/action/params
     *     overrides for the internal request.
     * @return string Response content, scalar dispatcher return value, or an
     *     empty string when the dispatcher returns null.
     * @throws ServiceException When the dispatcher service is missing or does
     *     not extend Phalcon's abstract dispatcher.
     * @throws \Throwable Propagates dispatcher and controller failures
     *     unchanged so HTTP/domain exceptions keep their original type and
     *     status semantics.
     */
    public function request(array $location = []): string
    {
        // Get a unique dispatcher
        $dispatcher = ServiceResolver::fromContainer(
            $this->getDI(),
            'dispatcher',
            AbstractDispatcher::class,
            context: 'HMVC application request'
        );
        $dispatcher = clone $dispatcher;
        
        // Route dispatcher
        $dispatcher->setDefaultNamespace($location['namespace'] ?? $dispatcher->getNamespaceName());
        $dispatcher->setNamespaceName($location['namespace'] ?? $dispatcher->getNamespaceName());
        $dispatcher->setModuleName($location['module'] ?? $dispatcher->getModuleName());
        if ($dispatcher instanceof MvcDispatcher) {
            $dispatcher->setControllerName($location['controller'] ?? 'index');
        }
        elseif ($dispatcher instanceof CliDispatcher) {
            $dispatcher->setTaskName($location['task'] ?? 'main');
        }
        $dispatcher->setActionName($location['action'] ?? 'index');
        $dispatcher->setParams($location['params'] ?? []);
        $dispatcher->dispatch();
        
        // Get and return value
        $response = $dispatcher->getReturnedValue();
        if ($response instanceof ResponseInterface) {
            return $response->getContent();
        }
        
        return $response ?? '';
    }
}
