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

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits;

use Phalcon\Di\FactoryDefault;
use Phalcon\Di\Di;
use Phalcon\Events\Manager;
use PhalconKit\Config\Config;
use PhalconKit\Mvc\Controller\Rest;
use PhalconKit\Mvc\Dispatcher;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\AttributePolicyController;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\BehaviorTestListener;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\InjectableBehaviorTestListener;
use PhalconKit\Tests\Unit\AbstractUnit;

class BehaviorTest extends AbstractUnit
{
    public function testBeforeExecuteRouteUsesDiEventsManagerWhenControllerManagerIsMissing(): void
    {
        $eventsManager = new Manager();
        $controller = $this->newController($eventsManager);
        
        $this->assertSame($eventsManager, $controller->getEventsManager());
        
        $controller->beforeExecuteRoute();
        
        $this->assertSame($eventsManager, $controller->getEventsManager());
        $this->assertTrue($eventsManager->arePrioritiesEnabled());
    }

    public function testAttachBehaviorUsesDiEventsManagerWhenControllerManagerIsMissing(): void
    {
        $eventsManager = new Manager();
        $controller = $this->newController($eventsManager);
        
        $controller->attachBehavior(BehaviorTestListener::class);
        
        $this->assertSame($eventsManager, $controller->getEventsManager());
        $this->assertCount(1, $eventsManager->getListeners('rest'));
    }

    public function testBeforeExecuteRouteAttachesConfiguredRoleFeatureAndModelBehaviors(): void
    {
        $eventsManager = new Manager();
        $controller = $this->newController($eventsManager, [
            'features' => [
                'feature-a' => [
                    'behaviors' => [
                        [BehaviorTestListener::class],
                    ],
                ],
            ],
            'roles' => [
                'blocked' => [
                    'behaviors' => [
                        [BehaviorTestListener::class],
                    ],
                ],
                'everyone' => [
                    'features' => ['feature-a'],
                    'behaviors' => [
                        get_class($this->newModelAwareController()) => [InjectableBehaviorTestListener::class],
                        'FooModel' => [BehaviorTestListener::class],
                    ],
                ],
            ],
        ], modelAware: true);

        $controller->beforeExecuteRoute();

        $this->assertNotEmpty($eventsManager->getListeners('rest'));
        $this->assertNotEmpty($eventsManager->getListeners('model'));
        $this->assertNotEmpty($eventsManager->getListeners('custom'));
    }

    public function testBeforeExecuteRouteAttachesActionScopedAttributeBehaviors(): void
    {
        $eventsManager = new Manager();
        $dispatcher = new Dispatcher();
        $dispatcher->setControllerName('attribute-policy');
        $dispatcher->setActionName('saveUserNode');

        $controller = $this->newAttributePolicyController($eventsManager, $dispatcher, [
            'roles' => [
                'manager' => [],
            ],
        ]);

        $controller->beforeExecuteRoute();

        $this->assertSame([], $eventsManager->getListeners('rest'));
        $this->assertCount(1, $eventsManager->getListeners('custom'));
    }

    public function testBeforeExecuteRouteAttachesDefaultEveryoneAttributeBehavior(): void
    {
        $eventsManager = new Manager();
        $dispatcher = new Dispatcher();
        $dispatcher->setControllerName('attribute-policy');
        $dispatcher->setActionName('find-with');

        $controller = $this->newAttributePolicyController($eventsManager, $dispatcher, [
            'roles' => [],
        ], roles: []);

        $controller->beforeExecuteRoute();

        $this->assertCount(1, $eventsManager->getListeners('rest'));
        $this->assertSame([], $eventsManager->getListeners('custom'));
    }

    public function testBeforeExecuteRouteCanDisableAttributeBehaviorScanning(): void
    {
        $eventsManager = new Manager();
        $dispatcher = new Dispatcher();
        $dispatcher->setControllerName('attribute-policy');
        $dispatcher->setActionName('find-with');

        $controller = $this->newAttributePolicyController($eventsManager, $dispatcher, [
            'roles' => [],
        ], roles: [], aclAttributes: false);

        $controller->beforeExecuteRoute();

        $this->assertSame([], $eventsManager->getListeners('rest'));
        $this->assertSame([], $eventsManager->getListeners('custom'));
    }

    public function testAttachBehaviorCreatesEventsManagerWhenDiDoesNotProvideOne(): void
    {
        $di = new Di();
        $di->setShared('config', new Config([
            'permissions' => [
                'features' => [],
                'roles' => [],
            ],
        ]));

        $controller = new class extends Rest {
        };
        $controller->setDI($di);

        $this->assertNull($controller->getEventsManager());

        $controller->attachBehaviors([BehaviorTestListener::class], 'rest');

        $this->assertInstanceOf(Manager::class, $controller->getEventsManager());
        $this->assertCount(1, $controller->getEventsManager()->getListeners('rest'));
    }

    private function newController(
        Manager $eventsManager,
        ?array $permissions = null,
        bool $modelAware = false
    ): Rest {
        $di = new FactoryDefault();
        $di->setShared('eventsManager', $eventsManager);
        $di->setShared('config', new Config([
            'permissions' => $permissions ?? [
                'features' => [],
                'roles' => [],
            ],
        ]));
        
        $controller = $modelAware ? $this->newModelAwareController() : new class extends Rest {
        };
        $controller->setDI($di);
        
        return $controller;
    }

    private function newModelAwareController(): Rest
    {
        $controller = new class extends Rest {
            public object $identity;

            public function getModelName(): ?string
            {
                return 'FooModel';
            }
        };
        $controller->identity = new class {
            public function hasRole(array|string $roles): bool
            {
                return in_array('admin', (array)$roles, true);
            }
        };

        return $controller;
    }

    private function newAttributePolicyController(
        Manager $eventsManager,
        Dispatcher $dispatcher,
        array $permissions,
        array $roles = ['manager'],
        bool $aclAttributes = true
    ): AttributePolicyController {
        $di = new FactoryDefault();
        $di->setShared('eventsManager', $eventsManager);
        $di->setShared('dispatcher', $dispatcher);
        $di->setShared('config', new Config([
            'acl' => [
                'attributes' => $aclAttributes,
            ],
            'permissions' => $permissions,
        ]));
        $di->setShared('identity', new class ($roles) {
            public function __construct(private readonly array $roles)
            {
            }

            public function hasRole(array|string $roles): bool
            {
                foreach ((array)$roles as $role) {
                    if (in_array($role, $this->roles, true)) {
                        return true;
                    }
                }

                return false;
            }
        });

        $controller = new AttributePolicyController();
        $controller->setDI($di);

        return $controller;
    }
}
