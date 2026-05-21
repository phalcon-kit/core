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
use Phalcon\Events\Manager;
use PhalconKit\Config\Config;
use PhalconKit\Mvc\Controller\Rest;
use PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures\BehaviorTestListener;
use PhalconKit\Tests\Unit\AbstractUnit;

class BehaviorTest extends AbstractUnit
{
    public function testBeforeExecuteRouteUsesDiEventsManagerWhenControllerManagerIsMissing(): void
    {
        $eventsManager = new Manager();
        $controller = $this->newController($eventsManager);
        
        $this->assertNull($controller->getEventsManager());
        
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
    
    private function newController(Manager $eventsManager): Rest
    {
        $di = new FactoryDefault();
        $di->setShared('eventsManager', $eventsManager);
        $di->setShared('config', new Config([
            'permissions' => [
                'features' => [],
                'roles' => [],
            ],
        ]));
        
        $controller = new class extends Rest {
        };
        $controller->setDI($di);
        
        return $controller;
    }
}
