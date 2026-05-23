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

namespace PhalconKit\Tests\Unit\Mvc\Dispatcher;

use Phalcon\Events\Event;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Mvc\Dispatcher\Maintenance;
use PhalconKit\Tests\Unit\AbstractUnit;

class MaintenanceTest extends AbstractUnit
{
    public function testBeforeDispatchDoesNotForwardWhenMaintenanceModeIsDisabled(): void
    {
        $plugin = new Maintenance();
        $plugin->setDI($this->createDi([
            'app' => [
                'maintenance' => false,
            ],
        ]));
        $dispatcher = $this->createDispatcher();
        $event = new Event('dispatch:beforeDispatch', $plugin);

        $plugin->beforeDispatch($event, $dispatcher);

        $this->assertSame('frontend', $dispatcher->getModuleName());
        $this->assertSame('index', $dispatcher->getControllerName());
        $this->assertSame('index', $dispatcher->getActionName());
        $this->assertFalse($event->isStopped());
    }

    public function testBeforeDispatchForwardsToDefaultMaintenanceRoute(): void
    {
        $plugin = new Maintenance();
        $plugin->setDI($this->createDi([
            'app' => [
                'maintenance' => true,
            ],
        ]));
        $dispatcher = $this->createDispatcher();
        $event = new Event('dispatch:beforeDispatch', $plugin);

        $plugin->beforeDispatch($event, $dispatcher);

        $this->assertSame('frontend', $dispatcher->getModuleName());
        $this->assertSame('error', $dispatcher->getControllerName());
        $this->assertSame('maintenance', $dispatcher->getActionName());
        $this->assertTrue($event->isStopped());
    }

    public function testBeforeDispatchForwardsToConfiguredMaintenanceRoute(): void
    {
        $plugin = new Maintenance();
        $plugin->setDI($this->createDi([
            'app' => [
                'maintenance' => true,
            ],
            'router' => [
                'maintenance' => [
                    'module' => 'admin',
                    'controller' => 'status',
                    'action' => 'offline',
                ],
            ],
        ]));
        $dispatcher = $this->createDispatcher();
        $event = new Event('dispatch:beforeDispatch', $plugin);

        $plugin->beforeDispatch($event, $dispatcher);

        $this->assertSame('admin', $dispatcher->getModuleName());
        $this->assertSame('status', $dispatcher->getControllerName());
        $this->assertSame('offline', $dispatcher->getActionName());
        $this->assertTrue($event->isStopped());
    }

    public function testBeforeDispatchRejectsInvalidConfigService(): void
    {
        $di = new Di();
        $di->set('config', new \stdClass());
        $plugin = new Maintenance();
        $plugin->setDI($di);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage(
            'Expected DI service "config" to be an instance of "PhalconKit\Config\ConfigInterface"; got "stdClass".'
        );

        $plugin->beforeDispatch(
            new Event('dispatch:beforeDispatch', $plugin),
            $this->createDispatcher()
        );
    }

    /**
     * Creates a PhalconKit DI with maintenance plugin configuration.
     *
     * @param array<string, mixed> $config Application config data.
     */
    private function createDi(array $config): Di
    {
        $di = new Di();
        $di->set('config', new Config($config));

        return $di;
    }

    /**
     * Creates an MVC dispatcher with an active route that can be forwarded.
     */
    private function createDispatcher(): Dispatcher
    {
        $dispatcher = new Dispatcher();
        $dispatcher->setModuleName('frontend');
        $dispatcher->setControllerName('index');
        $dispatcher->setActionName('index');

        return $dispatcher;
    }
}
