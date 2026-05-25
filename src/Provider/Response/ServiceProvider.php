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

namespace PhalconKit\Provider\Response;

use PhalconKit\Di\DiInterface;
use PhalconKit\Http\Response;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the HTTP response service.
 *
 * The response service wraps Phalcon's native response with PhalconKit's HTTP
 * contract and applies globally configured headers from `response.headers`.
 * This keeps security, cache, and platform headers centralized in config
 * instead of scattering them across controllers.
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'response';
    
    /**
     * Register the shared `response` service.
     *
     * Headers configured under `response.headers` are applied as soon as the
     * response is built. Applications that replace this provider should either
     * preserve that config behavior or document their own header strategy.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
    
            $response = new Response();
            $response->setDI($di);
            
            $headers = $config->pathToArray('response.headers') ?? [];
            foreach ($headers as $name => $value) {
                $response->setHeader($name, $value);
            }

            return $response;
        });
    }
}
