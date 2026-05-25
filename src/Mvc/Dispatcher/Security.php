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
 * Dispatcher listener that enforces configured ACL permissions.
 *
 * The listener compares the active controller/task class and action against
 * PhalconKit ACL components. When permissions are not configured it allows the
 * request, preserving the framework's permissive default for applications that
 * have not opted into ACL configuration.
 */
class Security extends Injectable
{
    /**
     * Check ACL permissions before Phalcon enters the dispatch loop.
     *
     * @param Event $event Dispatch event emitted by Phalcon.
     * @param AbstractDispatcher $dispatcher Active MVC or CLI dispatcher.
     *
     * @return bool True when dispatch can continue, false after forwarding.
     *
     * @throws DispatcherException When dispatcher state cannot be inspected.
     */
    public function beforeDispatchLoop(Event $event, AbstractDispatcher $dispatcher): bool
    {
        return $this->checkAcl($event, $dispatcher);
    }
    
    /**
     * Determine whether the current identity may execute the active handler.
     *
     * Unauthorized users with more than one ACL role are forwarded to
     * `router.unauthorized`; users with only one role are forwarded to
     * `router.forbidden`. Missing ACL components forward to `router.notFound`.
     *
     * @param Event $event Dispatch event emitted by Phalcon.
     * @param AbstractDispatcher|null $dispatcher Dispatcher to inspect. When
     *     omitted, the injected dispatcher service is used.
     *
     * @return bool True when dispatch can continue, false after forwarding.
     *
     * @throws DispatcherException When dispatcher state cannot be inspected.
     */
    public function checkAcl(Event $event, ?AbstractDispatcher $dispatcher = null): bool
    {
        $dispatcher ??= $this->dispatcher;
        
        $componentNames = ['components'];
        
        // Collect the route parts needed to detect authorization-forward cycles.
        $module = $dispatcher->getModuleName();
        $namespace = $dispatcher->getNamespaceName();
        
        if ($dispatcher instanceof MvcDispatcher) {
            $controller = $dispatcher->getControllerName();
            $componentNames [] = 'controllers';
        }
        
        if ($dispatcher instanceof CliDispatcher) {
            $task = $dispatcher->getTaskName();
            $componentNames [] = 'tasks';
        }
        
        $handler = $controller ?? $task ?? null;
        $handlerRouteKey = $dispatcher instanceof CliDispatcher ? 'task' : 'controller';
        $handlerClass = $dispatcher->getHandlerClass();
        $action = $dispatcher->getActionName();
        
        // ACL components are grouped by shared components plus handler type.
        $acl = $this->acl->get($componentNames);
        
        // Unknown handlers are treated as not-found instead of forbidden.
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
