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

/**
 * Base MVC module definition used by PhalconKit web modules.
 *
 * The module wires controller/model/transformer namespaces and configures the
 * dispatcher, router, view, and URL services for one module. Concrete modules
 * only need to provide the public `$name` value unless they need custom
 * namespaces or service registration behavior.
 */
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
     * Register controller/model/transformer namespaces for the MVC module.
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
     * Resolve and configure dispatcher, router, view, and URL services.
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
    
    /**
     * Resolve module-owned services from DI or create local defaults.
     *
     * @param DiInterface|null $container Optional DI container used by Phalcon
     *     module registration.
     */
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
    
    /**
     * Store resolved module services back into the active DI container.
     */
    public function setServices(DiInterface $container): void
    {
        $container->set('config', $this->config);
        $container->set('dispatcher', $this->dispatcher);
        $container->set('loader', $this->loader);
        $container->set('router', $this->router);
        $container->set('view', $this->view);
        $container->set('url', $this->url);
    }
    
    /**
     * Return namespace-to-directory mappings registered by the module loader.
     *
     * @return array<string, string>
     */
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
    
    /**
     * Return the default controller namespace for dispatcher routing.
     */
    public function getDefaultNamespace(): string
    {
        return $this->getNamespace() . '\\Controllers';
    }
    
    /**
     * Return the view directory list for this module.
     *
     * @return array<int, string>
     */
    public function getViewsDir(): array
    {
        return [$this->getDirname() . '/Views/'];
    }
    
    /**
     * Return the filesystem directory that contains this module class.
     */
    public function getDirname(): string
    {
        return Utils::getDirname($this);
    }
    
    /**
     * Return the PHP namespace for this module class.
     */
    public function getNamespace(): string
    {
        return Utils::getNamespace($this);
    }
}
