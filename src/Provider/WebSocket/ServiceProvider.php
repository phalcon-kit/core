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

namespace PhalconKit\Provider\WebSocket;

use PhalconKit\Di\DiInterface;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Ws\WebSocket;

/**
 * Registers the WebSocket application service.
 *
 * WebSocket handling uses the task-style `PhalconKit\Ws\WebSocket` entrypoint
 * rather than the MVC application. Keeping it under its own DI service name
 * lets bootstraps select WebSocket routing and dispatching without overloading
 * the HTTP or CLI application services.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'webSocket';
    
    /**
     * Register the shared `webSocket` service.
     *
     * The service receives the active DI container so WebSocket task modules can
     * resolve the same configured services as CLI and MVC modules.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            return new WebSocket($di);
        });
    }
}
