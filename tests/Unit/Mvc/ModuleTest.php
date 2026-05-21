<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc;

use Phalcon\Autoload\Loader;
use Phalcon\Di\Di;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Mvc\Module;
use PhalconKit\Mvc\Router;
use PhalconKit\Mvc\Url;
use PhalconKit\Mvc\View;
use PhalconKit\Tests\Unit\AbstractUnit;

class ModuleTest extends AbstractUnit
{
    public function testGetNamespacesUsesModuleNamespaceAndCoreModels(): void
    {
        $module = $this->createModule();
        $namespaces = $module->getNamespaces();

        $this->assertSame(__DIR__ . '/Controllers/', $namespaces[__NAMESPACE__ . '\\Controllers']);
        $this->assertSame(__DIR__ . '/Models/', $namespaces[__NAMESPACE__ . '\\Models']);
        $this->assertSame(__DIR__ . '/Transformers/', $namespaces[__NAMESPACE__ . '\\Transformers']);
        $this->assertStringEndsWith('/src/Models/', $namespaces['PhalconKit\\Models']);
    }

    public function testDefaultNamespaceViewsDirAndDirnameComeFromModuleClass(): void
    {
        $module = $this->createModule();

        $this->assertSame(__NAMESPACE__ . '\\Controllers', $module->getDefaultNamespace());
        $this->assertSame([__DIR__ . '/Views/'], $module->getViewsDir());
        $this->assertSame(__DIR__, $module->getDirname());
        $this->assertSame(__NAMESPACE__, $module->getNamespace());
    }

    public function testGetServicesUsesContainerServicesWhenAvailable(): void
    {
        $module = $this->createModule();
        $config = new Config();
        $di = new Di();
        $di->set('config', $config);
        $di->set('loader', new Loader());
        $di->set('router', new Router(false, $config));
        $di->set('dispatcher', new Dispatcher());
        $di->set('view', new View());
        $di->set('url', new Url());

        $module->getServices($di);

        $this->assertSame($di->get('config'), $module->config);
        $this->assertSame($di->get('loader'), $module->loader);
        $this->assertSame($di->get('router'), $module->router);
        $this->assertSame($di->get('dispatcher'), $module->dispatcher);
        $this->assertSame($di->get('view'), $module->view);
        $this->assertSame($di->get('url'), $module->url);
    }

    public function testSetServicesRegistersModuleServicesInContainer(): void
    {
        $module = $this->createModule();
        $module->config = new Config();
        $module->loader = new Loader();
        $module->router = new Router(false, $module->config);
        $module->dispatcher = new Dispatcher();
        $module->view = new View();
        $module->url = new Url();

        $di = new Di();
        $module->setServices($di);

        $this->assertSame($module->config, $di->get('config'));
        $this->assertSame($module->loader, $di->get('loader'));
        $this->assertSame($module->router, $di->get('router'));
        $this->assertSame($module->dispatcher, $di->get('dispatcher'));
        $this->assertSame($module->view, $di->get('view'));
        $this->assertSame($module->url, $di->get('url'));
    }

    public function testRegisterAutoloadersAddsModuleNamespacesToLoader(): void
    {
        $module = $this->createModule();
        $loader = new Loader();
        $di = new Di();
        $di->set('loader', $loader);

        $module->registerAutoloaders($di);

        $this->assertSame($loader, $module->loader);
        $loaderNamespaces = $loader->getNamespaces();
        foreach ($module->getNamespaces() as $namespace => $path) {
            $this->assertArrayHasKey($namespace . '\\', $loaderNamespaces);
            $this->assertContains($path, $loaderNamespaces[$namespace . '\\']);
        }
    }

    public function testRegisterServicesConfiguresModuleDefaultsAndContainerServices(): void
    {
        $module = $this->createModule();
        $config = new Config();
        $url = new Url();
        $url->setBasePath('/base');
        $di = new Di();
        $di->set('config', $config);
        $di->set('loader', new Loader());
        $di->set('router', new Router(false, $config));
        $di->set('dispatcher', new Dispatcher());
        $di->set('view', new View());
        $di->set('url', $url);

        $module->registerServices($di);

        $this->assertSame($module->config, $di->get('config'));
        $this->assertSame($module->loader, $di->get('loader'));
        $this->assertSame($module->router, $di->get('router'));
        $this->assertSame($module->dispatcher, $di->get('dispatcher'));
        $this->assertSame($module->view, $di->get('view'));
        $this->assertSame($module->url, $di->get('url'));
        $this->assertSame(__NAMESPACE__ . '\\Controllers', $module->dispatcher->getDefaultNamespace());
        $this->assertSame(__NAMESPACE__ . '\\Controllers', $module->dispatcher->getNamespaceName());
        $this->assertSame([__DIR__ . '/Views/'], $module->view->getViewsDir());
        $this->assertSame('/base/foo/', $module->url->getBasePath());
        $this->assertSame([
            'namespace' => __NAMESPACE__ . '\\Controllers',
            'module' => 'foo',
            'controller' => 'index',
            'action' => 'index',
            'params' => [],
        ], $module->router->getDefaults());
    }

    public function testParentNamespaceAndDirnameUseModuleReflection(): void
    {
        $module = new class extends Module {
            public string $name = 'native';
        };
        $reflection = new \ReflectionClass($module);

        $this->assertSame($reflection->getNamespaceName(), $module->getNamespace());
        $this->assertSame(dirname((string)$reflection->getFileName()), $module->getDirname());
    }

    private function createModule(): Module
    {
        return new class extends Module {
            public string $name = 'foo';

            public function getNamespace(): string
            {
                return __NAMESPACE__;
            }

            public function getDirname(): string
            {
                return __DIR__;
            }
        };
    }
}
