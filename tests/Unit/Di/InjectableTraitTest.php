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

namespace PhalconKit\Tests\Unit\Di;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Di\InjectionAwareInterface;
use Phalcon\Flash\Session;
use Phalcon\Session\Bag;
use Phalcon\Session\BagInterface;
use PHPUnit\Framework\TestStatus\Notice;
use PhalconKit\Bootstrap;
use PhalconKit\Di\InjectableTrait;
use PhalconKit\Tests\Unit\AbstractUnit;

class InjectableTraitTest extends AbstractUnit
{
    public $injectable;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->injectable = new class extends \stdClass implements InjectionAwareInterface {
            use InjectableTrait;
        };
    }
    
    public function testInjectableTrait(): void
    {
        $this->assertInstanceOf(\Phalcon\Di\DiInterface::class, $this->injectable->__get('di'));
        $this->assertInstanceOf(\Phalcon\Di\DiInterface::class, $this->injectable->getDI());
        $this->assertInstanceOf(\Phalcon\Di\DiInterface::class, $this->injectable->di);
        
        $this->assertSame($this->di, $this->injectable->__get('di'));
        $this->assertSame($this->di, $this->injectable->getDI());
        $this->assertSame($this->di, $this->injectable->di);
        
        $this->assertTrue(isset($this->injectable->di));
        $this->assertFalse(isset($this->injectable->nonExistingProperty));
        
        // bootstrap should be defined
        $this->assertInstanceOf(Bootstrap::class, $this->injectable->bootstrap);
    }

    public function testSetDIUsesCustomContainerAndCachesSharedServices(): void
    {
        $di = new Di();
        $service = new \stdClass();
        $di->setShared('customService', $service);

        $this->injectable->setDI($di);

        $this->assertSame($di, $this->injectable->getDI());
        $this->assertTrue(isset($this->injectable->customService));
        $this->assertSame($service, $this->injectable->customService);
        $this->assertSame($service, $this->injectable->instanceContainer['customService']);
    }

    public function testMagicGetCachesDiInstance(): void
    {
        $this->assertArrayNotHasKey('di', $this->injectable->instanceContainer);

        $this->assertSame($this->di, $this->injectable->di);
        $this->assertSame($this->di, $this->injectable->instanceContainer['di']);
    }
    
    public function testPersistentSessionBag(): void
    {
        // persistent should return sessionBag
        $this->di->set('sessionBag', new Bag($this->di->get('session'), 'default'));
        $this->assertInstanceOf(BagInterface::class, $this->injectable->persistent);
    }
    
    public function testThrowNonExistingSessionBag(): void
    {
        // unset sessionBag and persistent should trigger an exception
        $this->expectException(\Phalcon\Di\Exception::class);
        $this->assertNull($this->injectable->persistent);
    }
    
    public function testThrowNonExistingProperty(): void
    {
        $this->setErrorHandler();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Access to undefined property/');
        $this->assertNull($this->injectable->__get('nonExistingProperty'));
        $this->assertNull($this->injectable->nonExistingProperty);
    }

    public function testUndefinedPropertyReturnsNullWhenWarningIsSuppressed(): void
    {
        $this->assertNull(@$this->injectable->nonExistingProperty);
    }
    
    public function testThrowNonExistingPropertyStrict(): void
    {
        $strictInjectable = new class implements InjectionAwareInterface {
            use InjectableTrait;
        };
        $strictInjectable->setDI(new Di());
        $this->setErrorHandler();
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Access to undefined property/');
        $this->assertNull($strictInjectable->__get('nonExistingProperty'));
        $this->assertNull($strictInjectable->nonExistingProperty);
    }
}
