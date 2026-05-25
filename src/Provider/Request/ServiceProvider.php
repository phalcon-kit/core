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

namespace PhalconKit\Provider\Request;

use PhalconKit\Di\DiInterface;
use PhalconKit\Http\Request;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the HTTP request service.
 *
 * PhalconKit's request extends the native Phalcon request with framework helper
 * methods for CORS, preflight detection, same-origin checks, and diagnostic
 * array export. The provider attaches the DI container so request consumers can
 * use the same service-aware behavior as other injectables when needed.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'request';
    
    /**
     * Register the shared `request` service.
     *
     * The request object is created lazily and receives the DI container before
     * it is returned. Applications that replace this provider should preserve
     * the `PhalconKit\Http\Request` contract expected by dispatcher plugins and
     * controllers.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {

            $request = new Request();
            $request->setDI($di);
            
            return $request;
        });
    }
}
