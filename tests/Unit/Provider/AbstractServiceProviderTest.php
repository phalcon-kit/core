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

namespace PhalconKit\Tests\Unit\Provider;

use PhalconKit\Di\Di;
use PhalconKit\Di\DiInterface;
use PhalconKit\Exception\LogicException;
use PhalconKit\Provider\AbstractServiceProvider;
use PhalconKit\Tests\Unit\AbstractUnit;

class AbstractServiceProviderTest extends AbstractUnit
{
    public function testConstructorRequiresServiceName(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('cannot have an empty name');

        new class (new Di()) extends AbstractServiceProvider {
            public function register(DiInterface $di): void
            {
            }
        };
    }

    public function testConstructorStoresDiAndRunsConfigure(): void
    {
        $di = new Di();
        $provider = new class ($di) extends AbstractServiceProvider {
            protected string $serviceName = 'testService';
            public bool $configured = false;

            public function configure(): void
            {
                $this->configured = true;
            }

            public function register(DiInterface $di): void
            {
                $di->setShared($this->getName(), fn (): string => 'registered');
            }
        };

        $this->assertSame('testService', $provider->getName());
        $this->assertSame($di, $provider->getDI());
        $this->assertTrue($provider->configured);

        $provider->boot();
        $provider->register($di);

        $this->assertSame('registered', $di->get('testService'));
    }
}
