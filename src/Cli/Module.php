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

class Module implements ModuleDefinitionInterface
{
    public const string NAME_CLI = 'cli';
    
    public string $name = self::NAME_CLI;
    
    public ?Config $config = null;
    
    public ?Dispatcher $dispatcher = null;
    
    public ?Loader $loader = null;
    
    public ?Router $router = null;
    
    /**
     * Registers an autoloader related to the frontend module
     *
     * When a loader service is registered, it must be a Phalcon autoloader.
     * Otherwise the module creates a local loader so lightweight CLI modules do
     * not need to pre-register one.
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
     * Registers services related to the module
     *
     * Registered replacements for `dispatcher` and `router` are resolved
     * through the shared service resolver so invalid module wiring fails before
     * the module mutates service state.
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
    
    public function setServices(DiInterface $container): void
    {
        $container->set('config', $this->config);
        $container->set('dispatcher', $this->dispatcher);
        $container->set('loader', $this->loader);
        $container->set('router', $this->router);
    }
    
    public function getDefaultNamespace(): string
    {
        return $this->getNamespace() . '\\Tasks';
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
