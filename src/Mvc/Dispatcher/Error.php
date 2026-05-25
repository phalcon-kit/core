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

/**
 * Dispatcher listener that maps missing handlers/actions and runtime failures.
 *
 * Missing controllers/actions are forwarded to the configured not-found route.
 * Other exceptions are forwarded to the configured fatal route only when debug
 * mode is disabled; in debug mode the original exception is rethrown so
 * developer tooling can render it.
 */
class Error extends Injectable
{
    /**
     * Fallback route used when `router.notFound` is not fully configured.
     *
     * @var array{module: ?string, namespace: ?string, controller: string, action: string}
     */
    public array $defaultNotFoundRoute = [
        'module' => null,
        'namespace' => null,
        'controller' => 'error',
        'action' => 'notFound',
    ];
    
    /**
     * Fallback route used when `router.error` is not fully configured.
     *
     * @var array{module: ?string, namespace: ?string, controller: string, action: string}
     */
    public array $defaultErrorRoute = [
        'module' => null,
        'namespace' => null,
        'controller' => 'error',
        'action' => 'fatal',
    ];
    
    /**
     * Forward dispatch exceptions to the configured error routes.
     *
     * @param Event $event Dispatcher event emitted by Phalcon.
     * @param Dispatcher $dispatcher PhalconKit MVC dispatcher.
     * @param NativeException $exception Exception raised during dispatch.
     *
     * @return bool False when the listener handled the exception by forwarding.
     *
     * @throws DispatchException When forwarding to the configured error route
     *     fails.
     * @throws NativeException When debug mode is enabled or the exception is
     *     not handled by this listener.
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
    
    /**
     * Merge missing route parts from a default route definition.
     *
     * @param array<string, mixed> $route Configured route override.
     * @param array<string, mixed> $default Fallback route parts.
     *
     * @return array<string, mixed>
     */
    public function appendDefaultToRoute(array $route, array $default): array
    {
        $route['module'] ??= $default['module'] ?? null;
        $route['namespace'] ??= $default['namespace'] ?? null;
        $route['controller'] ??= $default['controller'] ?? null;
        $route['action'] ??= $default['action'] ?? null;
        return $route;
    }
}
