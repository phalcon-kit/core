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

namespace PhalconKit\Tests\Unit\Cli;

use PhalconKit\Bootstrap;
use PhalconKit\Cli\Module;
use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Tests\Unit\AbstractUnit;

class ModuleTest extends AbstractUnit
{
    public \PhalconKit\Cli\Module $module;
    
    protected string $mode = Bootstrap::MODE_CLI;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->module = new Module();
    }
    
    public function testRegisterAutoloaders(): void
    {
        $this->module->registerAutoloaders($this->di);
        $this->assertIsArray($this->module->loader->getNamespaces());
        $this->assertNotEmpty($this->module->loader->getNamespaces());
        $this->assertTrue($this->module->loader->isRegistered());
    }
    
    public function testRegisterServices(): void
    {
        $this->module->registerServices($this->di);
        $this->assertEquals('PhalconKit\\Cli\\Tasks', $this->module->dispatcher->getDefaultNamespace());
        $this->assertEquals('PhalconKit\\Cli\\Tasks', $this->module->dispatcher->getNamespaceName());
    }
    
    public function testGetNamespaces(): void
    {
        $namespaces = $this->module->getNamespaces();
        $this->assertArrayHasKey('PhalconKit\\Cli\\Tasks', $namespaces);
        $this->assertArrayHasKey('PhalconKit\\Cli\\Models', $namespaces);
        $this->assertArrayHasKey('PhalconKit\\Models', $namespaces);
        
        $this->assertIsString($namespaces['PhalconKit\\Cli\\Tasks']);
        $this->assertIsString($namespaces['PhalconKit\\Cli\\Models']);
        $this->assertIsString($namespaces['PhalconKit\\Models']);
    }
    
    public function testGetNamespace(): void
    {
        $namespace = $this->module->getNamespace();
        $this->assertEquals('PhalconKit\\Cli', $namespace);
    }
    
    public function testGetDefaultNamespace(): void
    {
        $defaultNamespace = $this->module->getDefaultNamespace();
        $this->assertEquals('PhalconKit\\Cli\\Tasks', $defaultNamespace);
    }
    
    public function testGetDirname(): void
    {
        $dirname = $this->module->getDirname();
        $this->assertIsString($dirname);
        $this->assertStringEndsWith(DIRECTORY_SEPARATOR . 'Cli', $dirname);
        $this->assertFileExists($dirname);
    }
    
    public function testGetServices(): void
    {
        $this->module->getServices();
        
        // Phalcon Instances
        $this->assertInstanceOf(\Phalcon\Config\Config::class, $this->module->config);
        $this->assertInstanceOf(\Phalcon\Autoload\Loader::class, $this->module->loader);
        $this->assertInstanceOf(\Phalcon\Cli\Router::class, $this->module->router);
        $this->assertInstanceOf(\Phalcon\Cli\Dispatcher::class, $this->module->dispatcher);
        
        // Phalcon Kit Instances
        $this->assertInstanceOf(\PhalconKit\Config\Config::class, $this->module->config);
        $this->assertInstanceOf(\PhalconKit\Cli\Router::class, $this->module->router);
        $this->assertInstanceOf(\PhalconKit\Cli\Dispatcher::class, $this->module->dispatcher);
        
        // Interfaces
        $this->assertInstanceOf(\PhalconKit\Router\RouterInterface::class, $this->module->router);
        $this->assertNotInstanceOf(\Phalcon\Cli\RouterInterface::class, $this->module->router);
        $this->assertInstanceOf(\Phalcon\Cli\Router::class, $this->module->router);
        $this->assertInstanceOf(\PhalconKit\Dispatcher\DispatcherInterface::class, $this->module->dispatcher);
        $this->assertInstanceOf(\Phalcon\Cli\DispatcherInterface::class, $this->module->dispatcher);
    }
    
    public function testSetServices(): void
    {
        $this->module->getServices($this->di);
        $this->module->setServices($this->di);
        $this->assertSame($this->di->get('config'), $this->module->config);
        $this->assertSame($this->di->get('dispatcher'), $this->module->dispatcher);
        $this->assertSame($this->di->get('loader'), $this->module->loader);
        $this->assertSame($this->di->get('router'), $this->module->router);
    }

    public function testGetServicesRejectsWrongRegisteredServiceType(): void
    {
        $di = new Di();
        $di->set('dispatcher', new \stdClass());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "dispatcher" to be an instance of "PhalconKit\Cli\Dispatcher"; got "stdClass".'
        );

        $this->module->getServices($di);
    }
}
