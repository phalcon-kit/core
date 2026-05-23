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

namespace PhalconKit\Mvc;

use Phalcon\Autoload\Loader;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Support\Utils;

abstract class Module implements ModuleDefinitionInterface
{
    public const string NAME_FRONTEND = 'frontend';
    public const string NAME_ADMIN = 'admin';
    public const string NAME_API = 'api';
    public const string NAME_OAUTH2 = 'oauth2';
    
    public string $name;
    
    public ?Config $config = null;
    
    public ?Dispatcher $dispatcher = null;
    
    public ?Loader $loader = null;
    
    public ?Router $router = null;
    
    public ?View $view = null;
    
    public ?Url $url = null;
    
    /**
     * Registers an autoloader related to the frontend module
     *
     * When the container defines a loader service, it must be compatible with
     * Phalcon's autoloader. Otherwise the module creates a local loader for the
     * module namespace registration.
     */
    #[\Override]
    public function registerAutoloaders(?DiInterface $container = null): void
    {
        $this->loader ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'loader',
                Loader::class,
                static fn () => new Loader(),
                context: 'MVC module autoloader'
            )
            : new Loader();
        $this->loader->setNamespaces($this->getNamespaces(), true);
        $this->loader->register();
    }
    
    /**
     * Registers services related to the module
     *
     * Registered replacements for module services are resolved through the
     * shared service resolver so invalid DI wiring fails before dispatcher,
     * router, view, or URL state is mutated.
     */
    #[\Override]
    public function registerServices(DiInterface $container): void
    {
        $this->getServices($container);
        $dispatcher = $this->dispatcher;
        $router = $this->router;
        $view = $this->view;
        $url = $this->url;
        if (
            !$dispatcher instanceof Dispatcher
            || !$router instanceof Router
            || !$view instanceof View
            || !$url instanceof Url
        ) {
            throw new ServiceException(
                'MVC module services were not initialized with compatible dispatcher, router, view, and URL instances.'
            );
        }

        $defaultNamespace = $this->getDefaultNamespace();
        $dispatcher->setDefaultNamespace($defaultNamespace);
        $dispatcher->setNamespaceName($defaultNamespace);
        $view->setViewsDir($this->getViewsDir());
        
        // url settings
        $url->setBasePath($url->getBasePath() . '/' . $this->name . '/');
        $router->setDefaults([
            'namespace' => $defaultNamespace,
            'module' => $this->name,
            'controller' => 'index',
            'action' => 'index',
        ]);
        
        // router settings
        $router->notFound([
            'controller' => 'error',
            'action' => 'notFound',
        ]);
        $router->removeExtraSlashes(true);
        
        $this->setServices($container);
    }
    
    public function getServices(?DiInterface $container = null): void
    {
        $this->loader ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'loader',
                Loader::class,
                static fn () => new Loader(),
                context: 'MVC module services'
            )
            : new Loader();
        $this->config ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'config',
                Config::class,
                static fn () => new Config(),
                context: 'MVC module services'
            )
            : new Config();
        $this->router ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'router',
                Router::class,
                fn () => new Router(false, $this->config ?? new Config()),
                context: 'MVC module services'
            )
            : new Router();
        $this->dispatcher ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'dispatcher',
                Dispatcher::class,
                static fn () => new Dispatcher(),
                context: 'MVC module services'
            )
            : new Dispatcher();
        $this->view ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'view',
                View::class,
                static fn () => new View(),
                context: 'MVC module services'
            )
            : new View();
        $this->url ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'url',
                Url::class,
                static fn () => new Url(),
                context: 'MVC module services'
            )
            : new Url();
    }
    
    public function setServices(DiInterface $container): void
    {
        $container->set('config', $this->config);
        $container->set('dispatcher', $this->dispatcher);
        $container->set('loader', $this->loader);
        $container->set('router', $this->router);
        $container->set('view', $this->view);
        $container->set('url', $this->url);
    }
    
    public function getNamespaces(): array
    {
        // Caller namespace
        $namespace = $this->getNamespace();
        $dirname = $this->getDirname();
        
        // register the vendor module controllers
        $namespaces = [];
        $namespaces[$namespace . '\\Controllers'] = $dirname . '/Controllers/';
        $namespaces[$namespace . '\\Models'] = $dirname . '/Models/';
        $namespaces[$namespace . '\\Transformers'] = $dirname . '/Transformers/';

        // add phalcon kit core models
        $corePath = dirname(__DIR__);
        $namespaces['PhalconKit\\Models'] = $corePath . '/Models/';
        
        return $namespaces;
    }
    
    public function getDefaultNamespace(): string
    {
        return $this->getNamespace() . '\\Controllers';
    }
    
    public function getViewsDir(): array
    {
        return [$this->getDirname() . '/Views/'];
    }
    
    public function getDirname(): string
    {
        return Utils::getDirname($this);
    }
    
    public function getNamespace(): string
    {
        return Utils::getNamespace($this);
    }
}
