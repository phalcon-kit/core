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

namespace PhalconKit\Provider\Application;

use PhalconKit\Di\DiInterface;
use PhalconKit\Mvc\Application;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the MVC application service.
 *
 * The application service is the Phalcon MVC entrypoint used by HTTP
 * bootstraps. It receives the shared PhalconKit DI container so dispatch,
 * module loading, events, routing, view, response, and other MVC services all
 * resolve from the same container instance.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'application';
    
    /**
     * Register the shared `application` service.
     *
     * The service is lazy and shared: the MVC application is only constructed
     * when first requested and subsequent lookups reuse the same instance.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            return new Application($di);
        });
    }
}
