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
use Phalcon\Events\Manager;
use Phalcon\Dispatcher\AbstractDispatcher;
use PHPUnit\Framework\Attributes\DataProvider;
use PhalconKit\Config\Config;
use PhalconKit\Di\Di;
use PhalconKit\Exception\ServiceException;
use PhalconKit\Http\Response;
use PhalconKit\Mvc\Controller\Traits\Actions\ErrorActions;
use PhalconKit\Mvc\Controller\Traits\StatusCode;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Mvc\Dispatcher\Maintenance;
use PhalconKit\Tests\Unit\AbstractUnit;

class MaintenanceTest extends AbstractUnit
{
    /** @return array<string, array{bool}> */
    public static function maintenanceModes(): array
    {
        return ['enabled' => [true], 'disabled' => [false]];
    }

    #[DataProvider('maintenanceModes')]
    public function testProviderListenersAndBundledErrorControllerCompleteDispatch(bool $enabled): void
    {
        $namespace = 'PhalconKit\\Modules\\Api\\Controllers';
        $config = $this->di->getConfig();
        $config->app->maintenance = $enabled;
        $config->app->debug = false;
        $config->debug->enable = false;
        $config->permissions = new Config(['roles' => ['everyone' => ['controllers' => [
            $namespace . '\\MaintenanceProbeController' => ['*'],
            $namespace . '\\ErrorController' => ['*'],
        ]]]]);
        $controller = new class extends \Phalcon\Mvc\Controller {
            public int $calls = 0;

            public function indexAction(): \Phalcon\Http\ResponseInterface
            {
                $this->calls++;
                return $this->response->setStatusCode(200)->setJsonContent(['normal' => true]);
            }
        };
        $controller->setDI($this->di);
        $this->di->setShared($namespace . '\\MaintenanceProbeController', $controller);
        $dispatcher = $this->di->getTyped('dispatcher', Dispatcher::class);
        $dispatcher->setDefaultNamespace($namespace);
        $dispatcher->setNamespaceName($namespace);
        $dispatcher->setModuleName('api');
        $dispatcher->setControllerName('maintenance-probe');
        $dispatcher->setActionName('index');
        $allowed = [];
        $executed = [];
        $dispatcher->getEventsManager()->attach('dispatch', static function (Event $event) use ($dispatcher, &$allowed, &$executed): void {
            if ($event->getType() === 'beforeDispatch') {
                $allowed[] = $dispatcher->getActionName();
            } elseif ($event->getType() === 'afterExecuteRoute') {
                $executed[] = $dispatcher->getActionName();
            }
        });

        $dispatcher->dispatch();

        $response = $dispatcher->getReturnedValue();
        $this->assertInstanceOf(\Phalcon\Http\ResponseInterface::class, $response);
        $this->assertSame($enabled ? 503 : 200, $response->getStatusCode());
        $this->assertSame($enabled ? 0 : 1, $controller->calls);
        $this->assertSame([$enabled ? 'maintenance' : 'index'], $allowed);
        $this->assertSame($allowed, $executed);
        $payload = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if ($enabled) {
            $this->assertSame(503, $payload['code']);
            $this->assertSame('Service Unavailable', $payload['status']);
        } else {
            $this->assertSame(['normal' => true], $payload);
        }
    }

    /** @return array<string, array{class-string<AbstractDispatcher>}> */
    public static function dispatcherClasses(): array
    {
        return [
            'Core MVC' => [Dispatcher::class],
            'Core CLI' => [\PhalconKit\Cli\Dispatcher::class],
            'Core WebSocket' => [\PhalconKit\Ws\Dispatcher::class],
            'native MVC' => [\Phalcon\Mvc\Dispatcher::class],
            'native CLI' => [\Phalcon\Cli\Dispatcher::class],
        ];
    }

    #[DataProvider('dispatcherClasses')]
    public function testDefaultRouteCompletesWithoutCancellingTargetListeners(string $dispatcherClass): void
    {
        $route = (new \PhalconKit\Bootstrap\Config())->pathToArray('router.maintenance');
        [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle($route, $dispatcherClass);

        $dispatcher->dispatch();

        $this->assertSame(0, $controller->indexCalls);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(2, $trace->before);
        $this->assertCount(1, $trace->allowed);
        $this->assertCount(1, $trace->executed);
        $this->assertSame('maintenance', $trace->executed[0]['action']);
    }

    #[DataProvider('dispatcherClasses')]
    public function testDisabledMaintenanceAllowsNormalDispatch(string $dispatcherClass): void
    {
        [$dispatcher, $di, $controller, $response, $trace] = $this->createDispatchCycle([], $dispatcherClass);
        $di->getConfig()->app->maintenance = false;

        $dispatcher->dispatch();

        $this->assertSame(1, $controller->indexCalls);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(1, $trace->before);
        $this->assertSame($trace->before, $trace->allowed);
        $this->assertSame($trace->before, $trace->executed);
    }

    #[DataProvider('dispatcherClasses')]
    public function testCustomMaintenanceRouteExecutesOnce(string $dispatcherClass): void
    {
        [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle([
            'namespace' => 'OtherMaintenanceTest',
            'module' => 'admin',
            'controller' => 'status',
            'task' => 'error',
            'action' => 'offline',
            'params' => ['scheduled'],
        ], $dispatcherClass);

        $dispatcher->dispatch();

        $this->assertSame(0, $controller->indexCalls);
        $this->assertSame(['scheduled'], $controller->offlineCalls);
        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(2, $trace->before);
        $this->assertCount(1, $trace->allowed);
        $this->assertSame([[
            'namespace' => 'OtherMaintenanceTest',
            'module' => $dispatcher instanceof \PhalconKit\Dispatcher\DispatcherInterface ? 'admin' : 'frontend',
            'handler' => 'status',
            'action' => 'offline',
            'params' => ['scheduled'],
        ]], $trace->executed);
    }

    #[DataProvider('dispatcherClasses')]
    public function testAlreadyAtMaintenanceTargetProceedsWithoutCancellation(string $dispatcherClass): void
    {
        [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle([], $dispatcherClass);
        $this->setHandlerName($dispatcher, 'error');
        $dispatcher->setActionName('maintenance');

        $dispatcher->dispatch();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(1, $trace->before);
        $this->assertSame($trace->before, $trace->allowed);
        $this->assertSame($trace->before, $trace->executed);
    }

    /** @return array<string, array{array<string, mixed>}> */
    public static function distinctTargets(): array
    {
        return [
            'controller with the same action' => [['controller' => 'status']],
            'module with the same controller and action' => [['module' => 'admin']],
            'namespace with the same controller and action' => [['namespace' => 'OtherMaintenanceTest']],
            'different parameters' => [['params' => ['tenant' => 'acme']]],
        ];
    }

    #[DataProvider('distinctTargets')]
    public function testOnlyTheCompleteConfiguredTargetMayExecute(array $route): void
    {
        [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle($route);
        $this->setHandlerName($dispatcher, 'error');
        $dispatcher->setActionName('maintenance');

        $dispatcher->dispatch();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(2, $trace->before);
        $this->assertCount(1, $trace->allowed);
        $this->assertSame([$trace->before[1]], $trace->executed);
        $this->assertNotSame($trace->before[0], $trace->executed[0]);
    }

    public function testNullAndOmittedCoreRoutePartsPreserveCurrentValues(): void
    {
        foreach ([[], ['namespace' => null, 'module' => null, 'controller' => null, 'action' => null, 'params' => null]] as $route) {
            [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle($route);
            $dispatcher->setNamespaceName('OtherMaintenanceTest');
            $dispatcher->setParameters(['tenant' => 'acme']);

            $dispatcher->dispatch();

            $this->assertSame(503, $response->getStatusCode());
            $this->assertCount(2, $trace->before);
            $this->assertSame([[
                'namespace' => 'OtherMaintenanceTest',
                'module' => 'frontend',
                'handler' => 'error',
                'action' => 'maintenance',
                'params' => ['tenant' => 'acme'],
            ]], $trace->executed);
        }
    }

    #[DataProvider('dispatcherClasses')]
    public function testEmptyRouteNamesUseDispatcherDefaults(string $dispatcherClass): void
    {
        [$dispatcher, , $controller, $response, $trace] = $this->createDispatchCycle([
            'namespace' => '', 'controller' => '', 'task' => '', 'action' => '', 'params' => [],
        ], $dispatcherClass);
        $dispatcher->setDefaultAction('offline');
        if ($dispatcher instanceof \Phalcon\Mvc\Dispatcher) {
            $dispatcher->setDefaultController('status');
        } else {
            $dispatcher->setDefaultTask('status');
        }

        $dispatcher->dispatch();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame([null], $controller->offlineCalls);
        $this->assertCount(2, $trace->before);
        $this->assertSame('status', $trace->executed[0]['handler']);
        $this->assertCount(1, $trace->allowed);
    }

    #[DataProvider('dispatcherClasses')]
    public function testNullNamespaceKeepsTheDispatchersOwnForwardingSemantics(string $dispatcherClass): void
    {
        [$dispatcher, , , $response, $trace] = $this->createDispatchCycle(['namespace' => null], $dispatcherClass);
        $dispatcher->setNamespaceName('OtherMaintenanceTest');

        if (!$dispatcher instanceof \PhalconKit\Dispatcher\DispatcherInterface) {
            // Phalcon 5.22's native forward rejects null for its typed namespace.
            $this->expectException(\TypeError::class);
            $this->expectExceptionMessage('namespaceName');
        }

        $dispatcher->dispatch();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertCount(1, $trace->allowed);
        $this->assertSame('OtherMaintenanceTest', $trace->executed[0]['namespace']);
    }

    #[DataProvider('dispatcherClasses')]
    public function testRepeatedDispatchCyclesReevaluateMaintenance(string $dispatcherClass): void
    {
        [$dispatcher, $di, $controller, $response, $trace] = $this->createDispatchCycle([], $dispatcherClass);
        foreach ([true, true, false, true] as $enabled) {
            $di->getConfig()->app->maintenance = $enabled;
            $this->setHandlerName($dispatcher, 'index');
            $dispatcher->setActionName('index');
            $response->setStatusCode(200);
            $trace->before = $trace->allowed = $trace->executed = [];

            $dispatcher->dispatch();

            $this->assertSame($enabled ? 503 : 200, $response->getStatusCode());
            $this->assertCount($enabled ? 2 : 1, $trace->before);
            $this->assertCount(1, $trace->allowed);
            $this->assertCount(1, $trace->executed);
            $this->assertSame($enabled ? 'maintenance' : 'index', $trace->executed[0]['action']);
        }
        $this->assertSame(1, $controller->indexCalls);
    }

    public function testDefaultMaintenanceActionExecutesOnceDuringRealDispatch(): void
    {
        $di = $this->createDi([
            'app' => ['maintenance' => true],
            'router' => ['maintenance' => (new \PhalconKit\Bootstrap\Config())->pathToArray('router.maintenance')],
        ]);
        $response = new Response();
        $di->setShared('response', $response);
        $controller = new class extends \Phalcon\Mvc\Controller {
            use ErrorActions;
            use StatusCode;

            public int $indexCalls = 0;

            public function indexAction(): void
            {
                $this->indexCalls++;
            }
        };
        $controller->setDI($di);
        $di->setShared('MaintenanceTest\\IndexController', $controller);
        $di->setShared('MaintenanceTest\\ErrorController', $controller);
        $dispatcher = $this->createDispatcher();
        $dispatcher->setDefaultNamespace('MaintenanceTest');
        $dispatcher->setDI($di);
        $events = new Manager();
        $plugin = new Maintenance();
        $plugin->setDI($di);
        $events->attach('dispatch', $plugin);
        $executions = 0;
        $events->attach('dispatch:afterExecuteRoute', static function () use (&$executions): void {
            $executions++;
        });
        $dispatcher->setEventsManager($events);

        $dispatcher->dispatch();

        $this->assertSame(0, $controller->indexCalls);
        $this->assertSame(1, $executions);
        $this->assertSame(503, $response->getStatusCode());
    }

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

    /** A native dispatch cycle with the real error action and observable listeners. */
    private function createDispatchCycle(array $route, string $dispatcherClass = Dispatcher::class): array
    {
        $di = $this->createDi(['app' => ['maintenance' => true], 'router' => ['maintenance' => $route]]);
        $response = new Response();
        $response->setStatusCode(200);
        $di->setShared('response', $response);
        $controller = new class extends \Phalcon\Mvc\Controller {
            use ErrorActions;
            use StatusCode;

            public int $indexCalls = 0;
            public array $offlineCalls = [];

            public function indexAction(): void
            {
                $this->indexCalls++;
            }

            public function offlineAction(?string $message = null): void
            {
                $this->offlineCalls[] = $message;
                $this->setStatusCode(503);
            }
        };
        $controller->setDI($di);
        $dispatcher = new $dispatcherClass();
        $dispatcher->setDI($di);
        $dispatcher->setDefaultNamespace('MaintenanceTest');
        $dispatcher->setModuleName('frontend');
        $dispatcher->setActionName('index');
        $this->setHandlerName($dispatcher, 'index');
        foreach (['MaintenanceTest', 'OtherMaintenanceTest'] as $namespace) {
            foreach (['Index', 'Error', 'Status'] as $handler) {
                $di->setShared($namespace . '\\' . $handler . $dispatcher->getHandlerSuffix(), $controller);
            }
        }
        $trace = (object)['before' => [], 'allowed' => [], 'executed' => []];
        $snapshot = static fn(): array => [
            'namespace' => $dispatcher->getNamespaceName(),
            'module' => $dispatcher->getModuleName(),
            'handler' => $dispatcher instanceof \Phalcon\Mvc\Dispatcher ? $dispatcher->getControllerName() : $dispatcher->getTaskName(),
            'action' => $dispatcher->getActionName(),
            'params' => $dispatcher->getParameters(),
        ];
        $events = new Manager();
        $events->enablePriorities(true);
        $events->attach('dispatch', static function (Event $event) use ($trace, $snapshot): void {
            if ($event->getType() === 'beforeDispatch') {
                $trace->before[] = $snapshot();
            }
        });
        $plugin = new Maintenance();
        $plugin->setDI($di);
        $events->attach('dispatch', $plugin);
        $events->attach('dispatch', static function (Event $event) use ($trace, $snapshot): void {
            if ($event->getType() === 'beforeDispatch') {
                $trace->allowed[] = $snapshot();
            } elseif ($event->getType() === 'afterExecuteRoute') {
                $trace->executed[] = $snapshot();
            }
        });
        $dispatcher->setEventsManager($events);

        return [$dispatcher, $di, $controller, $response, $trace];
    }

    private function setHandlerName(AbstractDispatcher $dispatcher, string $handler): void
    {
        if ($dispatcher instanceof \Phalcon\Mvc\Dispatcher) {
            $dispatcher->setControllerName($handler);
        } else {
            $dispatcher->setTaskName($handler);
        }
    }
}
