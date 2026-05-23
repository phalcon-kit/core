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

class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'url';
    
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
