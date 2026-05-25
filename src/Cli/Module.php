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

namespace PhalconKit\Cli;

use Phalcon\Autoload\Loader;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModuleDefinitionInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\ServiceResolver;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Support\Utils;

/**
 * Default CLI module definition.
 *
 * The module wires CLI task namespaces, dispatcher defaults, router defaults,
 * and core model namespaces for the built-in command runtime. Applications can
 * subclass or replace this module when they need different task namespaces or
 * service defaults, but should keep the same dispatcher/router contracts.
 */
class Module implements ModuleDefinitionInterface
{
    /**
     * Built-in CLI module name used by bootstrap module maps.
     */
    public const string NAME_CLI = 'cli';
    
    /**
     * Module name written into router defaults.
     */
    public string $name = self::NAME_CLI;
    
    /**
     * Config service resolved or created by the module.
     */
    public ?Config $config = null;
    
    /**
     * CLI dispatcher resolved or created by the module.
     */
    public ?Dispatcher $dispatcher = null;
    
    /**
     * Autoloader used to register task/model namespaces.
     */
    public ?Loader $loader = null;
    
    /**
     * CLI router resolved or created by the module.
     */
    public ?Router $router = null;
    
    /**
     * Register task/model namespaces for the CLI module.
     *
     * When a loader service is registered, it must be a Phalcon autoloader.
     * Otherwise the module creates a local loader so lightweight CLI modules do
     * not need to pre-register one.
     *
     * @param DiInterface|null $container Optional container supplied by
     *     Phalcon's module registration flow.
     *
     * @throws ServiceException When the registered loader is not compatible.
     */
    #[\Override]
    public function registerAutoloaders(?DiInterface $container = null): void
    {
        $this->loader = $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'loader',
                Loader::class,
                static fn () => new Loader(),
                context: 'CLI module autoloader'
            )
            : new Loader();
        $this->loader->setNamespaces($this->getNamespaces(), true);
        $this->loader->register();
    }
    
    /**
     * Resolve and configure dispatcher/router services for CLI execution.
     *
     * Registered replacements for `dispatcher` and `router` are resolved
     * through the shared service resolver so invalid module wiring fails before
     * the module mutates service state.
     *
     * @param DiInterface $container Container receiving the configured module
     *     services.
     *
     * @throws ServiceException When resolved module services are incompatible.
     */
    #[\Override]
    public function registerServices(DiInterface $container): void
    {
        $this->getServices($container);
        $dispatcher = $this->dispatcher;
        $router = $this->router;
        if (!$dispatcher instanceof Dispatcher || !$router instanceof Router) {
            throw new ServiceException('CLI module services were not initialized with compatible dispatcher and router instances.');
        }

        // dispatcher settings
        $defaultNamespace = $this->getDefaultNamespace();
        $dispatcher->setDefaultNamespace($defaultNamespace);
        $dispatcher->setNamespaceName($defaultNamespace);
        
        // router settings
        $router->setDefaults([
            'namespace' => $defaultNamespace,
            'module' => $this->name,
            'controller' => 'help',
            'action' => 'main',
        ]);
        
        $this->setServices($container);
    }
    
    /**
     * Return namespace-to-directory mappings registered by the module loader.
     *
     * @return array<string, string>
     */
    public function getNamespaces(): array
    {
        $namespaces = [];
        
        // Caller namespace
        $namespace = $this->getNamespace();
        $dirname = $this->getDirname();
        
        // register the vendor module controllers
        $namespaces[$namespace . '\\Tasks'] = $dirname . '/Tasks/';
        $namespaces[$namespace . '\\Models'] = $dirname . '/Models/';
    
        // add phalcon kit core models
        $corePath = dirname(__DIR__);
        $namespaces['PhalconKit\\Models'] = $corePath . '/Models/';
        
        return $namespaces;
    }
    
    /**
     * Resolve module-owned services from DI or create local defaults.
     *
     * @param DiInterface|null $container Optional DI container used by Phalcon
     *     module registration.
     *
     * @throws ServiceException When a registered replacement service has the
     *     wrong type or cannot be resolved.
     */
    public function getServices(?DiInterface $container = null): void
    {
        $this->loader = $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'loader',
                Loader::class,
                static fn () => new Loader(),
                context: 'CLI module services'
            )
            : new Loader();
        $this->config ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'config',
                Config::class,
                static fn () => new Config(),
                context: 'CLI module services'
            )
            : new Config();
        $this->router ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'router',
                Router::class,
                static fn () => new Router(),
                context: 'CLI module services'
            )
            : new Router();
        $this->dispatcher ??= $container !== null
            ? ServiceResolver::fromContainerOrDefault(
                $container,
                'dispatcher',
                Dispatcher::class,
                static fn () => new Dispatcher(),
                context: 'CLI module services'
            )
            : new Dispatcher();
    }
    
    /**
     * Store resolved module services back into the active DI container.
     *
     * @param DiInterface $container Container that should receive the resolved
     *     config, dispatcher, loader, and router services.
     */
    public function setServices(DiInterface $container): void
    {
        $container->set('config', $this->config);
        $container->set('dispatcher', $this->dispatcher);
        $container->set('loader', $this->loader);
        $container->set('router', $this->router);
    }
    
    /**
     * Return the default task namespace for dispatcher routing.
     */
    public function getDefaultNamespace(): string
    {
        return $this->getNamespace() . '\\Tasks';
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
