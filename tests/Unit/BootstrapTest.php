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

namespace PhalconKit\Tests\Unit;

use Phalcon\Application\AbstractApplication;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\RouterInterface as MvcRouterInterface;
use PhalconKit\Bootstrap;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Exception;
use PhalconKit\Cli\Console;
use PhalconKit\Mvc\Router as MvcRouter;
use PhalconKit\Cli\Router as CliRouter;

/**
 * Class BootstrapTest
 * @package Tests\Unit
 */
class BootstrapTest extends AbstractUnit
{
    protected function setUp(): void
    {
        /**
         * This setup method is intentionally left empty.
         * This test class does not require any specific initialization or fixtures.
         */
    }
    
    /**
     * Testing the bootstrap service
     */
    public function testMvcBootstrap(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_MVC);
        $this->assertInstanceOf(Bootstrap::class, $bootstrap);
        $this->assertInstanceOf(DiInterface::class, $bootstrap->di);
        $this->assertInstanceOf(DiInterface::class, $bootstrap->getDI());
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->config);
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->getConfig());
        $this->assertEquals(Bootstrap::MODE_MVC, $bootstrap->getMode());
        
        $this->assertEquals('mvc', Bootstrap::MODE_MVC);
        $this->assertEquals('cli', Bootstrap::MODE_CLI);
        $this->assertEquals('ws', Bootstrap::MODE_WS);
        
        $this->assertEquals(false, $bootstrap->isCli());
        $this->assertEquals(true, $bootstrap->isMvc());
        $this->assertEquals(false, $bootstrap->isWs());
        
        $bootstrap->setConfig(new Config());
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->getConfig());
        
        $bootstrap->setMode(Bootstrap::MODE_CLI);
        $this->assertEquals(Bootstrap::MODE_CLI, $bootstrap->getMode());
        
        $this->assertEquals(true, $bootstrap->isCli());
        $this->assertEquals(false, $bootstrap->isMvc());
        $this->assertEquals(false, $bootstrap->isWs());
        
        $bootstrap->setMode(Bootstrap::MODE_WS);
        $this->assertEquals(Bootstrap::MODE_WS, $bootstrap->getMode());
        
        $this->assertEquals(false, $bootstrap->isCli());
        $this->assertEquals(false, $bootstrap->isMvc());
        $this->assertEquals(true, $bootstrap->isWs());
        
        $this->assertTrue($bootstrap->di->has('bootstrap'));
        $this->assertTrue($bootstrap->di->has('config'));
        $this->assertTrue($bootstrap->di->has('application'));
        $this->assertTrue($bootstrap->di->has('router'));
        
        $this->assertInstanceOf(Bootstrap::class, $bootstrap->di->get('bootstrap'));
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->di->get('config'));
        $this->assertInstanceOf(AbstractApplication::class, $bootstrap->di->get('application'));
        $this->assertInstanceOf(MvcRouterInterface::class, $bootstrap->di->get('router'));
        $this->assertInstanceOf(MvcRouter::class, $bootstrap->di->get('router'));
    }
    
    public function testCliBootstrap(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);
        $this->assertInstanceOf(Bootstrap::class, $bootstrap);
        $this->assertInstanceOf(DiInterface::class, $bootstrap->di);
        $this->assertInstanceOf(DiInterface::class, $bootstrap->getDI());
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->config);
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->getConfig());
    
        $this->assertEquals(true, $bootstrap->isCli());
        $this->assertEquals(false, $bootstrap->isMvc());
        $this->assertEquals(false, $bootstrap->isWs());
    
        $this->assertTrue($bootstrap->di->has('bootstrap'));
        $this->assertTrue($bootstrap->di->has('config'));
        $this->assertTrue($bootstrap->di->has('console'));
        $this->assertTrue($bootstrap->di->has('router'));
    
        $this->assertInstanceOf(Bootstrap::class, $bootstrap->di->get('bootstrap'));
        $this->assertInstanceOf(ConfigInterface::class, $bootstrap->di->get('config'));
        $this->assertInstanceOf(AbstractApplication::class, $bootstrap->di->get('console'));
//        $this->assertInstanceOf(CliRouterInterface::class, $bootstrap->di->get('router')); // phalcon bug
        $this->assertInstanceOf(CliRouter::class, $bootstrap->di->get('router'));
    }
    
    public function testBootstrapArgs(): void
    {
        $server = $_SERVER;
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);
        
        $_SERVER['argv'] = [
            './phalcon-kit',
            'module',
            'task',
        ];
        
        $args = $bootstrap->getArgs();
        $this->assertIsArray($args);
        $this->assertEquals('module', $args['module']);
        $this->assertEquals('task', $args['task']);
        
        $_SERVER['argv'] = [
            './phalcon-kit',
            'module',
            'task',
            'action',
        ];
        $args = $bootstrap->getArgs();
        $this->assertIsArray($args);
        $this->assertEquals('module', $args['module']);
        $this->assertEquals('task', $args['task']);
        $this->assertEquals('action', $args['action']);

        $_SERVER = $server;
    }

    public function testBootstrapArgsParsesOptionsBeforeCommandParts(): void
    {
        $server = $_SERVER;
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);

        $_SERVER['argv'] = [
            './phalcon-kit',
            '--debug',
            '--format=json',
            'api',
            'foo',
            'bar',
            'one',
            'two',
        ];

        $args = $bootstrap->getArgs();

        $this->assertSame('api', $args['module']);
        $this->assertSame('foo', $args['task']);
        $this->assertSame('bar', $args['action']);
        $this->assertTrue($args['debug']);
        $this->assertSame('json', $args['format']);
        $this->assertSame(['one', 'two'], $args['params']);

        $_SERVER = $server;
    }

    public function testRegisterServicesRejectsNonStringProvider(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service Provider `bad` class name must be a string.');

        $bootstrap->registerServices([
            'bad' => 123,
        ]);
    }

    public function testRegisterServicesRejectsMissingProviderClass(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service Provider `bad` class `Missing\\Provider` not found.');

        $bootstrap->registerServices([
            'bad' => 'Missing\\Provider',
        ]);
    }

    public function testRegisterServicesRejectsClassThatIsNotProvider(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Service Provider `stdClass` must implement ServiceProviderInterface.');

        $bootstrap->registerServices([
            'bad' => \stdClass::class,
        ]);
    }

    public function testRegisterModulesAppliesProvidedModulesAndDefaultModule(): void
    {
        $bootstrap = new Bootstrap(Bootstrap::MODE_CLI);
        $console = new Console($bootstrap->getDI());
        $modules = [
            'foo' => [
                'className' => \stdClass::class,
            ],
        ];

        $bootstrap->registerModules($console, $modules, 'foo');

        $this->assertSame($modules, $console->getModules());
        $this->assertSame('foo', $console->getDefaultModule());
    }
}
