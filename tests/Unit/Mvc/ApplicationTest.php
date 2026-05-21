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

use Phalcon\Di\FactoryDefault;
use Phalcon\Http\Response;
use PhalconKit\Cli\Dispatcher as CliDispatcher;
use PhalconKit\Mvc\Application;
use PhalconKit\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Tests\Unit\AbstractUnit;

class ApplicationTest extends AbstractUnit
{
    public function testConstructorRegistersApplicationAsSharedService(): void
    {
        $di = new FactoryDefault();
        $application = new Application($di);

        $this->assertInstanceOf(\Phalcon\Mvc\Application::class, $application);
        $this->assertSame($application, $di->get('application'));
        $this->assertSame($di, $application->getDI());
    }

    public function testRequestDispatchesMvcLocationAndReturnsResponseContent(): void
    {
        $di = new FactoryDefault();
        $dispatcher = new class extends MvcDispatcher {
            /** @var array<string, mixed> */
            public static array $lastDispatch = [];

            public mixed $unitReturn = null;

            public function dispatch()
            {
                self::$lastDispatch = [
                    'namespace' => $this->getNamespaceName(),
                    'module' => $this->getModuleName(),
                    'controller' => $this->getControllerName(),
                    'action' => $this->getActionName(),
                    'params' => $this->getParams(),
                ];
                $this->setReturnedValue($this->unitReturn);
            }
        };
        $dispatcher->unitReturn = new Response('rendered content');
        $dispatcherClass = $dispatcher::class;
        $di->set('dispatcher', $dispatcher);

        $application = new Application($di);
        $content = $application->request([
            'namespace' => 'App\\Modules\\Api\\Controllers',
            'module' => 'api',
            'controller' => 'records',
            'action' => 'find',
            'params' => ['id' => 98],
        ]);

        $this->assertSame('rendered content', $content);
        $this->assertSame([
            'namespace' => 'App\\Modules\\Api\\Controllers',
            'module' => 'api',
            'controller' => 'records',
            'action' => 'find',
            'params' => ['id' => 98],
        ], $dispatcherClass::$lastDispatch);
    }

    public function testRequestDispatchesCliLocationAndReturnsScalarContent(): void
    {
        $di = new FactoryDefault();
        $dispatcher = new class extends CliDispatcher {
            /** @var array<string, mixed> */
            public static array $lastDispatch = [];

            public mixed $unitReturn = 'cli content';

            public function dispatch()
            {
                self::$lastDispatch = [
                    'namespace' => $this->getNamespaceName(),
                    'module' => $this->getModuleName(),
                    'task' => $this->getTaskName(),
                    'action' => $this->getActionName(),
                    'params' => $this->getParams(),
                ];
                $this->setReturnedValue($this->unitReturn);
            }
        };
        $dispatcherClass = $dispatcher::class;
        $di->set('dispatcher', $dispatcher);

        $application = new Application($di);
        $content = $application->request([
            'namespace' => 'App\\Tasks',
            'module' => 'cli',
            'task' => 'reports',
            'action' => 'run',
            'params' => ['projectId' => 98],
        ]);

        $this->assertSame('cli content', $content);
        $this->assertSame([
            'namespace' => 'App\\Tasks',
            'module' => 'cli',
            'task' => 'reports',
            'action' => 'run',
            'params' => ['projectId' => 98],
        ], $dispatcherClass::$lastDispatch);
    }

    public function testRequestReturnsEmptyStringForNullDispatcherReturnValue(): void
    {
        $di = new FactoryDefault();
        $dispatcher = new class extends MvcDispatcher {
            public function dispatch()
            {
                $this->setReturnedValue(null);
            }
        };
        $di->set('dispatcher', $dispatcher);

        $application = new Application($di);

        $this->assertSame('', $application->request());
    }
}
