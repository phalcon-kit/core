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

namespace PhalconKit\Tests\Unit\Ws;

use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Ws\Dispatcher;
use PhalconKit\Ws\Module;
use PhalconKit\Ws\Router;

class ModuleTest extends AbstractUnit
{
    public function testGetServicesUsesDefaultsWhenContainerHasNoOverrides(): void
    {
        $module = new Module();

        $module->getServices(new Di());

        $this->assertInstanceOf(\Phalcon\Autoload\Loader::class, $module->loader);
        $this->assertInstanceOf(\PhalconKit\Bootstrap\Config::class, $module->config);
        $this->assertInstanceOf(Router::class, $module->router);
        $this->assertInstanceOf(\Phalcon\Cli\Router::class, $module->router);
        $this->assertInstanceOf(\PhalconKit\Router\RouterInterface::class, $module->router);
        $this->assertInstanceOf(\Phalcon\Cli\RouterInterface::class, $module->router);
        $this->assertInstanceOf(Dispatcher::class, $module->dispatcher);
    }

    public function testRegisterServicesConfiguresDispatcherAndRouterDefaults(): void
    {
        $module = new Module();
        $di = new Di();

        $module->registerServices($di);

        $this->assertSame('PhalconKit\Ws\Tasks', $module->dispatcher->getDefaultNamespace());
        $this->assertSame('PhalconKit\Ws\Tasks', $module->dispatcher->getNamespaceName());
        $this->assertSame($module->dispatcher, $di->get('dispatcher'));
        $this->assertSame($module->router, $di->get('router'));
    }

    public function testGetServicesRejectsWrongRegisteredServiceType(): void
    {
        $module = new Module();
        $di = new Di();
        $di->set('router', new \stdClass());

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "router" to be an instance of "PhalconKit\Ws\Router"; got "stdClass".'
        );

        $module->getServices($di);
    }
}
