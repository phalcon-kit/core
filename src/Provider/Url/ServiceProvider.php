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

namespace PhalconKit\Provider\Url;

use PhalconKit\Di\DiInterface;
use Phalcon\Mvc;
use PhalconKit\Mvc\Url;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the URL generation service.
 *
 * The provider wraps Phalcon's MVC URL service with `PhalconKit\Mvc\Url`, which
 * preserves native route generation while normalizing local paths. Configuration
 * is read from `url.staticBaseUri`, `url.baseUri`, and `url.basePath`.
 *
 * @see https://docs.phalcon.io/5.13/url/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'url';
    
    /**
     * Register the shared `url` service.
     *
     * When the active router implements Phalcon's MVC router interface it is
     * passed to the URL service so named-route generation stays available.
     * CLI/WebSocket routers are ignored because they do not provide MVC URL
     * route generation.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function () use ($di) {
            
            $config = $di->getConfig();
            $urlConfig = $config->pathToArray('url') ?? [];
            
            $router = $di->get('router');
            $url = new Url($router instanceof Mvc\RouterInterface ? $router : null);
            $url->setStaticBaseUri($urlConfig['staticBaseUri'] ?? '/');
            $url->setBaseUri($urlConfig['baseUri'] ?? '/');
            $url->setBasePath($urlConfig['basePath'] ?? '/');
            $url->setDI($di);
            
            return $url;
        });
    }
}
