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
use PhalconKit\Exception\HttpException;
use PhalconKit\Http\StatusCode;
use PhalconKit\Mvc\Dispatcher;

/**
 * Dispatcher listener that maps request and runtime exceptions to error routes.
 *
 * Missing controllers/actions are forwarded to the configured not-found route.
 * HttpException instances preserve valid 400-599 status codes and are handled
 * through the configured HTTP-exception route in every environment. Other
 * exceptions are forwarded to the configured fatal route only when debug mode
 * is disabled; in debug mode the original exception is rethrown so developer
 * tooling can render it.
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
     * Fallback route used when `router.httpException` is not fully configured.
     *
     * Applications may override the route target, but status normalization
     * remains owned by this listener.
     *
     * @var array{module: ?string, namespace: ?string, controller: string, action: string}
     */
    public array $defaultHttpExceptionRoute = [
        'module' => null,
        'namespace' => null,
        'controller' => 'error',
        'action' => 'error',
    ];
    
    /**
     * Fallback route used when `router.fatal` is not fully configured.
     *
     * The property name is retained for compatibility with applications that
     * customize the listener directly.
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
     * Forward dispatch and HTTP exceptions to the configured error routes.
     *
     * @param Event $event Dispatcher event emitted by Phalcon.
     * @param Dispatcher $dispatcher PhalconKit MVC dispatcher.
     * @param NativeException $exception Exception raised during dispatch.
     *
     * @return bool False when the listener handled the exception by forwarding.
     *
     * @throws DispatchException When forwarding to the configured error route
     *     fails.
     * @throws NativeException When debug mode is enabled for an unexpected
     *     exception.
     */
    public function beforeException(Event $event, Dispatcher $dispatcher, NativeException $exception): bool
    {
        if ($exception instanceof HttpException) {
            return $this->forwardHttpException($dispatcher, $exception);
        }

        if (
            $exception instanceof DispatchException
            && in_array($exception->getCode(), [
                DispatchException::EXCEPTION_HANDLER_NOT_FOUND,
                DispatchException::EXCEPTION_ACTION_NOT_FOUND,
            ], true)
        ) {
            $route = $this->config->pathToArray('router.notFound') ?? [];
            
            $route = $this->appendDefaultToRoute($route, $this->defaultNotFoundRoute);
            $route['params']['exception'] = $exception;

            $dispatcher->forward($route, true);
            return false;
        }

        return $this->forwardFatalException($dispatcher, $exception);
    }

    /**
     * Normalize an HttpException code to an HTTP error status.
     *
     * Only HttpException owns this numeric-code contract. Invalid codes fail
     * closed as 500 instead of being sent as successful or malformed statuses.
     */
    private function normalizeHttpExceptionStatus(HttpException $exception): int
    {
        $status = $exception->getCode();

        return $status >= 400 && $status <= 599
            ? $status
            : StatusCode::INTERNAL_SERVER_ERROR;
    }

    /**
     * Resolve a framework-owned reason phrase for any accepted error status.
     *
     * Phalcon requires a non-empty phrase for non-standard numeric statuses.
     * Unmapped 4xx/5xx codes therefore use their category's generic phrase
     * instead of exposing the exception message as transport metadata.
     */
    private function getHttpReasonPhrase(int $status): string
    {
        $fallbackStatus = $status < StatusCode::INTERNAL_SERVER_ERROR
            ? StatusCode::BAD_REQUEST
            : StatusCode::INTERNAL_SERVER_ERROR;

        return StatusCode::getMessage($status) ?? StatusCode::$messages[$fallbackStatus];
    }

    /**
     * Forward an expected request exception without handing it to debug output.
     */
    private function forwardHttpException(Dispatcher $dispatcher, HttpException $exception): bool
    {
        $status = $this->normalizeHttpExceptionStatus($exception);
        $this->response->setStatusCode($status, $this->getHttpReasonPhrase($status));

        $route = $this->config->pathToArray('router.httpException') ?? [];
        $route = $this->appendDefaultToRoute($route, $this->defaultHttpExceptionRoute);
        $route['params'] = array_merge($route['params'] ?? [], [
            'code' => $status,
            'exception' => $exception,
        ]);

        $dispatcher->forward($route, true);
        return false;
    }

    /**
     * Forward an unexpected production exception or rethrow it for debug tools.
     *
     * @throws NativeException When either application debug flag is enabled.
     */
    private function forwardFatalException(Dispatcher $dispatcher, NativeException $exception): bool
    {
        $appDebug = $this->config->path('app.debug', false);
        $debugEnable = $this->config->path('debug.enable', false);

        if ($appDebug || $debugEnable) {
            throw $exception;
        }

        $status = StatusCode::INTERNAL_SERVER_ERROR;
        $this->response->setStatusCode($status, $this->getHttpReasonPhrase($status));

        $route = $this->config->pathToArray('router.fatal') ?? [];
        $route = $this->appendDefaultToRoute($route, $this->defaultErrorRoute);
        $route['params']['exception'] = $exception;

        $dispatcher->forward($route, true);
        return false;
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
