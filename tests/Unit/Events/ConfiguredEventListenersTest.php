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

namespace PhalconKit\Tests\Unit\Events;

use Phalcon\Events\Manager;
use PhalconKit\Di\Di;
use PhalconKit\Events\ConfiguredEventListeners;
use PhalconKit\Exception\ConfigurationException;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Events\Fixtures\ConfiguredDisabledListener;
use PhalconKit\Tests\Unit\Events\Fixtures\ConfiguredEventListenerState;
use PhalconKit\Tests\Unit\Events\Fixtures\ConfiguredHighPriorityListener;
use PhalconKit\Tests\Unit\Events\Fixtures\ConfiguredLowPriorityListener;

class ConfiguredEventListenersTest extends AbstractUnit
{
    protected function setUp(): void
    {
        /**
         * This test class builds its own small DI/event-manager fixtures.
         */
    }

    protected function tearDown(): void
    {
        ConfiguredEventListenerState::reset();
        parent::tearDown();
    }

    public function testAttachResolvesClassesServicesAndPriorities(): void
    {
        ConfiguredEventListenerState::reset();
        $di = new Di();
        $serviceListener = new ConfiguredLowPriorityListener();
        $eventsManager = new Manager();

        $di->setShared('lowPriorityListener', $serviceListener);

        ConfiguredEventListeners::attach($di, $eventsManager, [
            'unit' => [
                [
                    'service' => 'lowPriorityListener',
                    'priority' => 100,
                ],
                [
                    'class' => ConfiguredHighPriorityListener::class,
                    'priority' => 200,
                ],
                [
                    'class' => ConfiguredDisabledListener::class,
                    'enabled' => false,
                ],
            ],
        ]);

        $eventsManager->fire('unit:beforeRun', $this, ['ok' => true]);

        $this->assertSame([
            ConfiguredHighPriorityListener::class,
            ConfiguredLowPriorityListener::class,
        ], array_column(ConfiguredEventListenerState::$calls, 'listener'));
        $this->assertSame([true, true], array_column(ConfiguredEventListenerState::$calls, 'hasDi'));
        $this->assertSame([
            ['ok' => true],
            ['ok' => true],
        ], array_column(ConfiguredEventListenerState::$calls, 'data'));
        $this->assertTrue($eventsManager->arePrioritiesEnabled());
    }

    public function testAttachRejectsInvalidListenerDefinition(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('must define "class" or "service"');

        ConfiguredEventListeners::attach(new Di(), new Manager(), [
            'unit' => [
                [
                    'priority' => 100,
                ],
            ],
        ]);
    }
}
