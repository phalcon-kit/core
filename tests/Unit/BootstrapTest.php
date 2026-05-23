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
use Phalcon\Di\Di as NativeDi;
use Phalcon\Mvc\RouterInterface as MvcRouterInterface;
use PhalconKit\Bootstrap;
use PhalconKit\Config\ConfigInterface;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Di\Di;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Cli\Console;
use PhalconKit\Mvc\Router as MvcRouter;
use PhalconKit\Cli\Router as CliRouter;
use PhalconKit\Support\Debug;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Bootstrap\Fixtures\BootstrapApplicationDouble;
use PhalconKit\Tests\Unit\Bootstrap\Fixtures\BootstrapConsoleDouble;
use PhalconKit\Tests\Unit\Bootstrap\Fixtures\BootstrapProviderDouble;
use PhalconKit\Tests\Unit\Bootstrap\Fixtures\BootstrapWebSocketDouble;
use PhalconKit\Tests\Unit\Bootstrap\Fixtures\LightweightBootstrap;

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

        $bootstrap->setMode();
        $this->assertEquals(Bootstrap::MODE_CLI, $bootstrap->getMode());
        
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

    public function testGetConfigRejectsUnregisteredConfig(): void
    {
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_MVC, new Di());

        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Bootstrap config has not been registered.');

        $bootstrap->getConfig();
    }

    public function testSetDiRejectsNativePhalconContainer(): void
    {
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_MVC, new Di());

        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage('must be of type ?PhalconKit\Di\DiInterface');

        \call_user_func([$bootstrap, 'setDI'], new NativeDi());
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

    public function testRegisterServicesRegistersValidProvider(): void
    {
        $di = new Di();
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_CLI, $di, new Config([
            'providers' => [],
        ]));

        $bootstrap->registerServices([
            'double' => BootstrapProviderDouble::class,
        ]);

        $this->assertTrue($di->has('bootstrapProviderDouble'));
        $this->assertSame('registered', $di->get('bootstrapProviderDouble'));
    }

    public function testRegisterConfigRouterAndBootServicesUseExistingDiServices(): void
    {
        $di = new Di();
        $config = new Config(['providers' => []]);
        $router = new MvcRouter(false, $config);
        $debug = new Debug();
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_MVC, $di);

        $di->set('config', $config);
        $di->set('router', $router);
        $di->set('debug', $debug);

        $bootstrap->registerConfig();
        $bootstrap->registerRouter();
        $bootstrap->bootServices();

        $this->assertSame($config, $bootstrap->getConfig());
        $this->assertSame($router, $bootstrap->getRouter());

        $newRouter = new MvcRouter(false, $config);
        $bootstrap->setRouter($newRouter);
        $this->assertSame($newRouter, $bootstrap->getRouter());
    }

    public function testRegisterRouterRegistersMissingRouterService(): void
    {
        $di = new Di();
        $config = new Config(['providers' => []]);
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_MVC, $di, $config);

        $di->set('config', $config);
        $di->set('eventsManager', $bootstrap->getEventsManager());
        $di->set('application', new BootstrapApplicationDouble($di));

        $bootstrap->registerRouter();

        $this->assertTrue($di->has('router'));
        $this->assertSame($di->get('router'), $bootstrap->getRouter());
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

    public function testRegisterModulesUsesDefaultWebSocketApplication(): void
    {
        $di = new Di();
        $webSocket = new BootstrapWebSocketDouble($di);
        $modules = [
            'ws' => [
                'className' => \stdClass::class,
            ],
        ];
        $config = new Config([
            'modules' => $modules,
            'router' => [
                'defaults' => [
                    'module' => 'ws',
                ],
            ],
        ]);
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_WS, $di, $config);

        $di->set('webSocket', $webSocket);

        $bootstrap->registerModules();

        $this->assertArrayHasKey('ws', $webSocket->getModules());
        $this->assertSame($modules['ws'], $webSocket->getModules()['ws']);
        $this->assertSame('ws', $webSocket->getDefaultModule());
    }

    public function testRegisterModulesRejectsUnsupportedMode(): void
    {
        $bootstrap = new LightweightBootstrap('unsupported', new Di(), new Config());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to register modules in bootstrap mode: `unsupported`');

        $bootstrap->registerModules();
    }

    public function testRunHandlesMvcCliAndWebSocketModes(): void
    {
        $server = $_SERVER;

        try {
            $_SERVER['REQUEST_URI'] = '/unit';
            $_SERVER['argv'] = ['./phalcon-kit', 'api', 'task'];

            $mvcDi = new Di();
            $application = new BootstrapApplicationDouble($mvcDi);
            $mvc = new LightweightBootstrap(Bootstrap::MODE_MVC, $mvcDi);
            $mvcDi->set('application', $application);

            $this->assertSame('mvc-content', $mvc->run());
            $this->assertSame('/unit', $application->handledUri);

            $cliDi = new Di();
            $console = new BootstrapConsoleDouble($cliDi);
            $cli = new LightweightBootstrap(Bootstrap::MODE_CLI, $cliDi);
            $cliDi->set('console', $console);

            $this->assertSame('console-content', $cli->run());
            $this->assertSame('api', $console->handledArguments['module']);
            $this->assertSame('task', $console->handledArguments['task']);

            $wsDi = new Di();
            $webSocket = new BootstrapWebSocketDouble($wsDi);
            $ws = new LightweightBootstrap(Bootstrap::MODE_WS, $wsDi);
            $wsDi->set('webSocket', $webSocket);

            $this->assertNull($ws->run());
            $this->assertTrue($webSocket->handled);
        } finally {
            $_SERVER = $server;
        }
    }

    public function testRunRejectsUnsupportedMode(): void
    {
        $bootstrap = new LightweightBootstrap('unsupported', new Di());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Unable to handle run application in bootstrap mode: `unsupported`');

        $bootstrap->run();
    }

    public function testHandleConsoleReturnsNullWhenConsoleThrows(): void
    {
        $server = $_SERVER;
        $bufferLevel = ob_get_level();
        $di = new Di();
        $console = new BootstrapConsoleDouble($di);
        $console->throw = true;
        $bootstrap = new LightweightBootstrap(Bootstrap::MODE_CLI, $di);
        $di->set('helper', new HelperFactory());

        try {
            $_SERVER['argv'] = ['./phalcon-kit', 'api', 'task'];

            $this->assertNull($bootstrap->handleConsole($console));
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $_SERVER = $server;
        }
    }
}
