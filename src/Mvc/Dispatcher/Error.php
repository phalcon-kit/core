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

use Exception as NativeException;
use Phalcon\Dispatcher\Exception as DispatchException;
use Phalcon\Events\Event;
use PhalconKit\Di\Injectable;
use PhalconKit\Mvc\Dispatcher;

class Error extends Injectable
{
    public array $defaultNotFoundRoute = [
        'module' => null,
        'namespace' => null,
        'controller' => 'error',
        'action' => 'notFound',
    ];
    
    public array $defaultErrorRoute = [
        'module' => null,
        'namespace' => null,
        'controller' => 'error',
        'action' => 'fatal',
    ];
    
    /**
     * Forward to 404 or 500 error controller
     * @throws DispatchException When forwarding to the configured error route
     *     fails.
     */
    public function beforeException(Event $event, Dispatcher $dispatcher, NativeException $exception): bool
    {
        switch ($exception->getCode()) {
            case DispatchException::EXCEPTION_HANDLER_NOT_FOUND:
            case DispatchException::EXCEPTION_ACTION_NOT_FOUND:
                if ($exception instanceof DispatchException) {
                    $route = $this->config->pathToArray('router.notFound') ?? [];
                    
                    $this->appendDefaultToRoute($route, $this->defaultNotFoundRoute);
                    $route['params']['exception'] = $exception;
                    
                    $dispatcher->forward($route, true);
                    return false;
                }
                break;
            
            default:
                http_response_code(500);
                
                // Everything else, if debug is false, forward to fatal error 500
                $appDebug = $this->config->path('app.debug', false);
                $debugEnable = $this->config->path('debug.enable', false);
                
                if (!$appDebug && !$debugEnable) {
                    $route = $this->config->pathToArray('router.error') ?? [];
                    
                    $this->appendDefaultToRoute($route, $this->defaultErrorRoute);
                    $route['params']['exception'] = $exception;
                    
                    $dispatcher->forward($route, true);
                    return false;
                }
                break;
        }
        throw $exception;
    }
    
    public function appendDefaultToRoute(array $route, array $default): array
    {
        $route['module'] ??= $default['module'] ?? null;
        $route['namespace'] ??= $default['namespace'] ?? null;
        $route['controller'] ??= $default['controller'] ?? null;
        $route['action'] ??= $default['action'] ?? null;
        return $route;
    }
}
