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
use PhalconKit\Cli\Router;
use PhalconKit\Tests\Unit\AbstractUnit;

class RouterTest extends AbstractUnit
{
    public \PhalconKit\Cli\Router $router;
    
    protected string $mode = Bootstrap::MODE_CLI;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->router = $this->di->get('router');
    }
    
    public function testRouterFromDi(): void
    {
        $this->assertInstanceOf(\PhalconKit\Router\RouterInterface::class, $this->router);
        $this->assertNotInstanceOf(\Phalcon\Cli\RouterInterface::class, $this->router);
        $this->assertInstanceOf(\Phalcon\Cli\Router::class, $this->router);
        $this->assertInstanceOf(\PhalconKit\Cli\Router::class, $this->router);
    }

    public function testNativeCliRouterInterfaceIsIncompatibleWithNativeCliRouter(): void
    {
        $nativeSetDefaultAction = new \ReflectionMethod(\Phalcon\Cli\Router::class, 'setDefaultAction');
        $interfaceSetDefaultAction = new \ReflectionMethod(\Phalcon\Cli\RouterInterface::class, 'setDefaultAction');
        $nativeGetRouteById = new \ReflectionMethod(\Phalcon\Cli\Router::class, 'getRouteById');
        $interfaceGetRouteById = new \ReflectionMethod(\Phalcon\Cli\RouterInterface::class, 'getRouteById');

        $this->assertSame(\Phalcon\Cli\Router::class, (string) $nativeSetDefaultAction->getReturnType());
        $this->assertSame('void', (string) $interfaceSetDefaultAction->getReturnType());
        $this->assertNull($nativeGetRouteById->getReturnType());
        $this->assertSame(
            \Phalcon\Cli\Router\RouteInterface::class,
            (string) $interfaceGetRouteById->getReturnType()
        );
    }
    
    public function testToArray(): void
    {
        $routerToArray = $this->router->toArray();
        $this->assertIsArray($routerToArray);
        $this->assertIsString($routerToArray['module']);
        $this->assertIsString($routerToArray['action']);
        $this->assertIsArray($routerToArray['params']);
        $this->assertIsArray($routerToArray['matches']);
        $this->assertNull($routerToArray['matched']);
    }
    
    public function testRealScenario(): void
    {
        $this->router->handle([
            'module' => 'cli',
            'task' => 'error',
            'action' => 'not-found',
            'params' => ['test' => 'test'],
        ]);
        $this->assertEquals('cli', $this->router->getModuleName());
        $this->assertEquals('error', $this->router->getTaskName());
        $this->assertEquals('not-found', $this->router->getActionName());
        $this->assertEquals(['test' => 'test'], $this->router->getParams());
    }

    public function testToArrayReflectsHandledRouteParts(): void
    {
        $this->router->handle([
            'module' => 'cli',
            'task' => 'cache',
            'action' => 'wipe',
            'params' => ['all' => true],
        ]);

        $routerToArray = $this->router->toArray();

        $this->assertSame('cli', $routerToArray['module']);
        $this->assertSame('cache', $routerToArray['task']);
        $this->assertSame('wipe', $routerToArray['action']);
        $this->assertSame(['all' => true], $routerToArray['params']);
    }
}
