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

namespace PhalconKit\Provider\View;

use PhalconKit\Di\DiInterface;
use Phalcon\Events\Manager;
use PhalconKit\Mvc\View;
use PhalconKit\Mvc\View\Error;
use PhalconKit\Provider\AbstractServiceProvider;

/**
 * Registers the MVC view service.
 *
 * The provider builds `PhalconKit\Mvc\View`, attaches the shared events
 * manager, registers the framework view-error listener, configures template
 * engines, and applies the optional `view.minify` setting. Consumers can pass
 * per-resolution options when manually resolving the service, otherwise
 * `view` config is used.
 *
 * @see https://docs.phalcon.io/5.16/views/
 */
class ServiceProvider extends AbstractServiceProvider
{
    protected string $serviceName = 'view';
    
    /**
     * Register the shared `view` service.
     *
     * Supported options currently include `minify` and `engines`. When engines
     * are omitted, PHP and Volt templates are registered using Phalcon's native
     * engine classes.
     */
    #[\Override]
    public function register(DiInterface $di): void
    {
        $di->setShared($this->getName(), function (?array $options = null) use ($di) {
    
            $config = $di->getConfig();
            
            $eventsManager = $di->getTyped('eventsManager', Manager::class);
            
            $options ??= $config->pathToArray('view', []);
            
            $error = new Error();
            $error->setDI($di);
            
            $eventsManager->attach('view', $error);
            
            $view = new View();
            $view->setMinify($options['minify'] ?? false);
            
            $view->registerEngines($options['engines'] ?? [
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php',
                '.volt' => 'Phalcon\Mvc\View\Engine\Volt',
            ]);
            
            $view->setEventsManager($eventsManager);
            $view->setDI($di);
            
            return $view;
        });
    }
}
