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

namespace PhalconKit\Tests\Unit\Dispatcher;

use Phalcon\Events\Event;
use PhalconKit\Bootstrap;
use PhalconKit\Cli\Dispatcher as CliDispatcher;
use PhalconKit\Dispatcher\AbstractDispatcher;
use PhalconKit\Dispatcher\DispatcherInterface;
use PhalconKit\Mvc\Dispatcher as MvcDispatcher;
use PhalconKit\Mvc\Dispatcher\Camelize;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\AbstractUnit;

class DispatcherTest extends AbstractUnit
{
    protected string $mode = Bootstrap::MODE_CLI;
    
    protected DispatcherInterface $dispatcher;
    protected \Phalcon\Mvc\DispatcherInterface $mvcDispatcher;
    protected \Phalcon\Cli\DispatcherInterface $cliDispatcher;
    
    protected function setUp(): void
    {
        $this->dispatcher = new AbstractDispatcher();
        $this->mvcDispatcher = new \PhalconKit\Mvc\Dispatcher();
        $this->cliDispatcher = new \PhalconKit\Cli\Dispatcher();
    }
    
    public function testDispatcherInstance(): void
    {
        // Abstract
        $this->assertInstanceOf(\PhalconKit\Dispatcher\AbstractDispatcher::class, $this->dispatcher);
        $this->assertInstanceOf(\PhalconKit\Dispatcher\DispatcherInterface::class, $this->dispatcher);
        
        $this->assertInstanceOf(\Phalcon\Dispatcher\AbstractDispatcher::class, $this->dispatcher);
        $this->assertInstanceOf(\Phalcon\Dispatcher\DispatcherInterface::class, $this->dispatcher);
        
        // MVC
        $this->assertInstanceOf(\PhalconKit\Mvc\Dispatcher::class, $this->mvcDispatcher);
        $this->assertInstanceOf(\PhalconKit\Dispatcher\DispatcherInterface::class, $this->mvcDispatcher);
        
        $this->assertInstanceOf(\Phalcon\Dispatcher\AbstractDispatcher::class, $this->mvcDispatcher);
        $this->assertInstanceOf(\Phalcon\Dispatcher\DispatcherInterface::class, $this->mvcDispatcher);
        $this->assertInstanceOf(\Phalcon\Mvc\DispatcherInterface::class, $this->mvcDispatcher);
        
        // CLI
        $this->assertInstanceOf(\PhalconKit\Cli\Dispatcher::class, $this->cliDispatcher);
        $this->assertInstanceOf(\PhalconKit\Dispatcher\DispatcherInterface::class, $this->cliDispatcher);
        
        $this->assertInstanceOf(\Phalcon\Dispatcher\AbstractDispatcher::class, $this->cliDispatcher);
        $this->assertInstanceOf(\Phalcon\Dispatcher\DispatcherInterface::class, $this->cliDispatcher);
        $this->assertInstanceOf(\Phalcon\Cli\DispatcherInterface::class, $this->cliDispatcher);
    }
    
    public function testCallActionMethod(): void
    {
        $handler = new class {
            public function actionMethod(int $param1 = 1, int $param2 = 1): int
            {
                return $param1 + $param2;
            }
        };
        
        $params = ['param1' => 'string_value', 0 => 1, 'param2' => 2, 1 => 3];
        $result = $this->dispatcher->callActionMethod($handler, 'actionMethod', $params);
        $this->assertEquals(4, $result, 'callActionMethod did not return expected result');
        
        $result = $this->dispatcher->callActionMethod($handler, 'actionMethod', []);
        $this->assertEquals(2, $result, 'callActionMethod did not return expected result');
        
        $result = $this->dispatcher->callActionMethod($handler, 'actionMethod', ['test' => 2, 'test2' => 2]);
        $this->assertEquals(2, $result, 'callActionMethod did not return expected result');
    }
    
    public function testForward(): void
    {
        $this->dispatcher->forward(['action' => 'notFound'], false);
        $this->assertSame('notFound', $this->dispatcher->getActionName());
        $this->assertSame('', $this->dispatcher->getModuleName());
        
        $this->dispatcher->forward(['action' => 'notFound'], true);
        $this->assertSame('notFound', $this->dispatcher->getActionName());
        
        $this->dispatcher->forward(['module' => 'admin', 'action' => 'maintenance'], true);
        $this->assertSame('admin', $this->dispatcher->getModuleName());
        $this->assertSame('maintenance', $this->dispatcher->getActionName());

        $this->dispatcher->forward(['action' => 'non-existing-action'], true);
        $this->assertSame('non-existing-action', $this->dispatcher->getActionName());
        
        $this->dispatcher->forward(['action' => 'non-existing-action'], false);
        $this->assertSame('non-existing-action', $this->dispatcher->getActionName());
    }
    
    public function testCanForward(): void
    {
        // Test Abstract Dispatcher
        $this->assertFalse($this->dispatcher->canForward([]));
        $this->assertFalse($this->dispatcher->canForward(['module' => '']));
        $this->assertTrue($this->dispatcher->canForward(['module' => 'test']));
        
        // With same values
        $forward = ['namespace' => 'namespace', 'module' => 'module', 'action' => 'action', 'params' => ['param' => true]];
        $this->dispatcher->setNamespaceName('namespace');
        $this->dispatcher->setModuleName('module');
        $this->dispatcher->setActionName('action');
        $this->dispatcher->setParams(['param' => true]);
        $this->assertFalse($this->dispatcher->canForward($forward));
        
        // Test MVC Dispatcher
        $this->assertFalse($this->mvcDispatcher->canForward([]));
        $this->assertFalse($this->mvcDispatcher->canForward(['controller' => '']));
        $this->assertTrue($this->mvcDispatcher->canForward(['controller' => 'new']));
        $this->assertFalse($this->mvcDispatcher->canForward(['task' => 'new']));
        
        // Test CLI Dispatcher
        $this->assertFalse($this->cliDispatcher->canForward([]));
        $this->assertFalse($this->cliDispatcher->canForward(['task' => '']));
        $this->assertTrue($this->cliDispatcher->canForward(['task' => 'new']));
        $this->assertFalse($this->cliDispatcher->canForward(['controller' => 'new']));
    }
    
    public function testUnsetForwardNullParts(): void
    {
        // Forward attributes with nonexistent parts
        $forwardWithNullParts = [
            'namespace' => 'Custom\Namespace',
            'module' => null,
            'task' => 'CustomTask',
            'controller' => null,
            'action' => 'actionName',
            'params' => ['param1', 'param2'],
        ];
        
        // Expected result after unsetting NULL parts
        $expectedForward = [
            'namespace' => 'Custom\Namespace',
            'task' => 'CustomTask',
            'action' => 'actionName',
            'params' => ['param1', 'param2'],
        ];
        
        $result = $this->dispatcher->unsetForwardNullParts($forwardWithNullParts);
        $this->assertSame($expectedForward, $result, "unsetForwardNullParts() does not unset NULL parts correctly!");
        
        // Try with all null parts
        $forwardWithNullParts = [
            'namespace' => null,
            'module' => null,
            'task' => null,
            'controller' => null,
            'action' => null,
            'params' => null,
        ];
        
        $result = $this->dispatcher->unsetForwardNullParts($forwardWithNullParts);
        $this->assertEquals([], $result, "unsetForwardNullParts() does not unset NULL parts correctly!");
        
        // Try with no null parts
        $forwardWithoutNullParts = [
            'namespace' => 'Custom\Namespace',
            'module' => 'Cli',
            'task' => 'CustomTask',
            'action' => 'actionName',
            'params' => ['param1', 'param2'],
        ];
        
        $result = $this->dispatcher->unsetForwardNullParts($forwardWithoutNullParts);
        $this->assertEquals($forwardWithoutNullParts, $result, "unsetForwardNullParts() does not keep parts correctly!");
    }

    public function testCamelizeNormalizesMvcControllerAndActionNames(): void
    {
        $dispatcher = new MvcDispatcher();
        $dispatcher->setControllerName('api-record');
        $dispatcher->setActionName('show-item');

        $camelize = $this->newCamelizeListener();
        $camelize->beforeDispatchLoop(new Event('beforeDispatchLoop', $camelize), $dispatcher);

        $this->assertSame('ApiRecord', $dispatcher->getControllerName());
        $this->assertSame('showItem', $dispatcher->getActionName());
    }

    public function testCamelizeNormalizesCliActionNameWithoutChangingTaskName(): void
    {
        $dispatcher = new CliDispatcher();
        $dispatcher->setTaskName('queue-worker');
        $dispatcher->setActionName('run-once');

        $camelize = $this->newCamelizeListener();
        $camelize->beforeDispatchLoop(new Event('beforeDispatchLoop', $camelize), $dispatcher);

        $this->assertSame('queue-worker', $dispatcher->getTaskName());
        $this->assertSame('runOnce', $dispatcher->getActionName());
    }

    public function testMvcSecurityForwardsLeanForbiddenRouteWithoutWarnings(): void
    {
        [$security, $event, $dispatcher] = $this->createDeniedMvcSecurityFixture('index');

        $this->withoutPhpWarnings(function () use ($security, $event, $dispatcher): void {
            $this->assertFalse($security->checkAcl($event, $dispatcher));
        });

        $this->assertSame('forbidden', $dispatcher->getActionName());
    }

    public function testMvcSecurityRecognizesCurrentLeanForbiddenRouteWithoutWarnings(): void
    {
        [$security, $event, $dispatcher] = $this->createDeniedMvcSecurityFixture('forbidden');

        $this->withoutPhpWarnings(function () use ($security, $event, $dispatcher): void {
            $this->assertTrue($security->checkAcl($event, $dispatcher));
        });

        $this->assertSame('forbidden', $dispatcher->getActionName());
    }
    
    public function testToArray(): void
    {
        $dispatcherArray = $this->dispatcher->toArray();
        $this->assertIsString($dispatcherArray['namespace']);
        $this->assertEquals('', $dispatcherArray['namespace']);
        $this->assertIsString($dispatcherArray['module']);
        $this->assertEquals('', $dispatcherArray['module']);
        $this->assertIsString($dispatcherArray['action']);
        $this->assertEquals('', $dispatcherArray['action']);
        $this->assertIsArray($dispatcherArray['params']);
        $this->assertEquals([], $dispatcherArray['params']);
        $this->assertIsString($dispatcherArray['handlerClass']);
        $this->assertEquals('', $dispatcherArray['handlerClass']);
        $this->assertIsString($dispatcherArray['handlerSuffix']);
        $this->assertEquals('', $dispatcherArray['handlerSuffix']);
        $this->assertIsString($dispatcherArray['activeMethod']);
        $this->assertEquals('Action', $dispatcherArray['activeMethod']);
        $this->assertIsString($dispatcherArray['actionSuffix']);
        $this->assertEquals('Action', $dispatcherArray['actionSuffix']);
        
        $mvcArray = $this->mvcDispatcher->toArray();
        $this->assertIsString($mvcArray['controller']);
        $this->assertEquals('index', $mvcArray['controller']);
        $this->assertIsString($mvcArray['previousNamespace']);
        $this->assertEquals('', $mvcArray['previousNamespace']);
        $this->assertIsString($mvcArray['previousController']);
        $this->assertEquals('', $mvcArray['previousController']);
        $this->assertIsString($mvcArray['previousAction']);
        $this->assertEquals('', $mvcArray['previousAction']);
        
        $cliArray = $this->cliDispatcher->toArray();
        $this->assertIsString($cliArray['task']);
        $this->assertEquals('main', $cliArray['task']);
        $this->assertIsString($cliArray['taskSuffix']);
        $this->assertEquals('Task', $cliArray['taskSuffix']);
    }

    /**
     * @return array{
     *     0: \PhalconKit\Mvc\Dispatcher\Security,
     *     1: \Phalcon\Events\Event,
     *     2: \PhalconKit\Mvc\Dispatcher
     * }
     */
    private function createDeniedMvcSecurityFixture(string $action): array
    {
        $dispatcher = new \PhalconKit\Mvc\Dispatcher();
        $dispatcher->setNamespaceName('App\\Controllers');
        $dispatcher->setModuleName('api');
        $dispatcher->setControllerName('unit');
        $dispatcher->setActionName($action);

        $di = new \Phalcon\Di\Di();
        $di->set('config', new \PhalconKit\Config\Config([
            'permissions' => [
                'roles' => [
                    'guest' => [],
                ],
            ],
            'router' => [
                'forbidden' => [
                    'action' => 'forbidden',
                ],
            ],
        ]));
        $di->set('acl', new class {
            public function get(array $componentNames = ['components']): \Phalcon\Acl\Adapter\Memory
            {
                $acl = new \Phalcon\Acl\Adapter\Memory();
                $acl->addRole(new \Phalcon\Acl\Role('guest'));
                $acl->addComponent(new \Phalcon\Acl\Component('App\\Controllers\\UnitController'), ['index', 'forbidden']);

                return $acl;
            }
        });
        $di->set('identity', new class {
            public function getAclRoles(): array
            {
                return ['guest'];
            }
        });

        $security = new \PhalconKit\Mvc\Dispatcher\Security();
        $security->setDI($di);

        return [$security, new \Phalcon\Events\Event('beforeDispatchLoop', $security), $dispatcher];
    }

    private function newCamelizeListener(): Camelize
    {
        $di = new \Phalcon\Di\Di();
        $di->set('helper', new HelperFactory());

        $camelize = new Camelize();
        $camelize->setDI($di);

        return $camelize;
    }

    private function withoutPhpWarnings(\Closure $callback): void
    {
        $handlerActive = true;
        set_error_handler(
            static function (int $code, string $message, string $file, int $line) use (&$handlerActive): never {
                $handlerActive = false;
                restore_error_handler();

                throw new \ErrorException($message, 0, $code, $file, $line);
            },
            E_WARNING
        );

        try {
            $callback();
        }
        finally {
            if ($handlerActive) {
                restore_error_handler();
            }
        }
    }
}
