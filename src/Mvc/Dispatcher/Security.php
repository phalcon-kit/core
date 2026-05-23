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

use Phalcon\Cli\Dispatcher as CliDispatcher;
use Phalcon\Dispatcher\AbstractDispatcher;
use Phalcon\Events\Event;
use Phalcon\Dispatcher\Exception as DispatcherException;
use Phalcon\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Di\Injectable;

/**
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Injectable
{
    /**
     * This action is executed before execute any action in the application
     * @throws DispatcherException When dispatcher state cannot be inspected.
     */
    public function beforeDispatchLoop(Event $event, AbstractDispatcher $dispatcher): bool
    {
        return $this->checkAcl($event, $dispatcher);
    }
    
    /**
     * Check if the current identity is allowed from the dispatcher
     * @throws DispatcherException When dispatcher state cannot be inspected.
     */
    public function checkAcl(Event $event, ?AbstractDispatcher $dispatcher = null): bool
    {
        $dispatcher ??= $this->dispatcher;
        
        $componentNames = ['components'];
        
        // get controller and action
        $module = $dispatcher->getModuleName();
        $namespace = $dispatcher->getNamespaceName();
        
        if ($dispatcher instanceof MvcDispatcher) {
            $controller = $dispatcher->getControllerName();
//            $controllerClass = $dispatcher->getControllerClass();
            $componentNames [] = 'controllers';
        }
        
        if ($dispatcher instanceof CliDispatcher) {
            $task = $dispatcher->getTaskName();
//            $taskSuffix = $dispatcher->getTaskSuffix();
            $componentNames [] = 'tasks';
        }
        
        $handler = $controller ?? $task ?? null;
        $handlerRouteKey = $dispatcher instanceof CliDispatcher ? 'task' : 'controller';
        $handlerClass = $dispatcher->getHandlerClass();
//        $handlerSuffix = $dispatcher->getHandlerSuffix();
        $action = $dispatcher->getActionName();
//        $actionSuffix = $dispatcher->getActionSuffix();
        
        // Get ACL components (components + task, or components + controllers)
        $acl = $this->acl->get($componentNames);
        
        // Security not found
        if (!$acl->isComponent($handlerClass)) {
            $notFoundRoute = $this->config->pathToArray('router.notFound') ?? [];
            $dispatcher->forward($notFoundRoute);
            return false;
        }
        
        $allowed = false;
        $roles = $this->identity->getAclRoles();
        
        foreach ($roles as $role) {
            $allowed = $acl->isAllowed($role, $handlerClass, $action);
            if ($allowed) {
                break;
            }
        }
        
        $permissions = $this->config->pathToArray('permissions');
        if (empty($permissions)) {
            $allowed = true;
        }
        
        if (!$allowed) {
            if (count($roles) > 1) {
                $unauthorizedRoute = $this->config->pathToArray('router.unauthorized') ?? [];
                if ($this->isCurrentRoute($unauthorizedRoute, $namespace, $module, $handlerRouteKey, $handler, $action)) {
                    return true;
                }
                $dispatcher->forward($unauthorizedRoute);
            }
            else {
                $forbiddenRoute = $this->config->pathToArray('router.forbidden') ?? [];
                if ($this->isCurrentRoute($forbiddenRoute, $namespace, $module, $handlerRouteKey, $handler, $action)) {
                    return true;
                }
                $dispatcher->forward($forbiddenRoute);
            }
            return false;
        }
        
        return true;
    }

    /**
     * Detect dispatcher cycles for full or partial configured routes.
     */
    private function isCurrentRoute(
        array $route,
        ?string $namespace,
        ?string $module,
        string $handlerRouteKey,
        ?string $handler,
        string $action
    ): bool {
        if (!array_key_exists('action', $route) || $route['action'] !== $action) {
            return false;
        }

        $currentRoute = [
            'namespace' => $namespace,
            'module' => $module,
            $handlerRouteKey => $handler,
        ];

        foreach ($currentRoute as $part => $currentValue) {
            if (!array_key_exists($part, $route)) {
                continue;
            }

            if ($route[$part] !== $currentValue) {
                return false;
            }
        }

        return true;
    }
}
