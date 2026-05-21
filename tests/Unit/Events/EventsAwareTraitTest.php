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

use Phalcon\Events\Event;
use Phalcon\Events\EventInterface;
use Phalcon\Events\Manager;
use Phalcon\Events\ManagerInterface;
use PhalconKit\Events\EventsAwareTrait;
use PhalconKit\Tests\Unit\AbstractUnit;

class EventsAwareTraitTest extends AbstractUnit
{
    public $events;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->events = new class {
            use EventsAwareTrait;
        };
    }
    
    public function testSetEventsManager(): void
    {
        $manager = new Manager();
        $this->events->setEventsManager($manager);
        $this->assertSame($manager, $this->events->getEventsManager());
    }
    
    public function testGetEventsManager(): void
    {
        $manager = $this->events->getEventsManager();
        $this->assertInstanceOf(ManagerInterface::class, $manager);
        $this->assertInstanceOf(Manager::class, $manager);
    }
    
    public function testGetEventsPrefix(): void
    {
        $this->assertIsString($this->events->getEventsPrefix());
        $this->assertNotEmpty($this->events->getEventsPrefix());
        $this->assertStringStartsWith('events-aware-trait-test-', $this->events->getEventsPrefix());
    }
    
    public function testSetEventsPrefix(): void
    {
        $this->events::setEventsPrefix('custom');
        $this->assertEquals('custom', $this->events->getEventsPrefix());
    }
    
    public function testFire(): void
    {
        $manager = new Manager();
        $data = ['data' => 'value'];
        $bag = [];
        $task = 'testFire';
        
        $manager->attach($this->events->getEventsPrefix() . ':' . $task, function (EventInterface $event, $subject, $data) use (&$bag) {
            $bag = [
                'event' => $event,
                'subject' => $subject,
                'data' => $data,
                'afterCancel' => false,
            ];
            return 'first-return';
        }, 0);
        
        $manager->attach($this->events->getEventsPrefix() . ':' . $task, function (EventInterface $event) {
            $event->stop();
            return 'second-return';
        }, 1);
        
        $manager->attach($this->events->getEventsPrefix() . ':' . $task, function () use (&$bag) {
            $bag['afterCancel'] = true;
            return 'third-return';
        }, 2);
        
        $this->events->setEventsManager($manager);
        $result = $this->events->fire($task, $data, true);
        
        $this->assertEquals('second-return', $result); // @todo why only second is returned and not third even if we do not stop event?
        $this->assertInstanceOf(Event::class, $bag['event']);
        $this->assertInstanceOf(EventInterface::class, $bag['event']);
        $this->assertInstanceOf($this->events::class, $bag['subject']);
        $this->assertEquals($data, $bag['event']->getData());
        $this->assertEquals($data, $bag['data']);
//        $this->assertFalse($bag['afterCancel']); // @todo why
    }

    public function testFireReturnsListenerResultForNonCancelableEvent(): void
    {
        $task = 'testFireReturnsListenerResultForNonCancelableEvent';
        $manager = new Manager();
        $manager->attach($this->events->getEventsPrefix() . ':' . $task, static function () {
            return 'listener-result';
        });

        $this->events->setEventsManager($manager);

        $this->assertSame('listener-result', $this->events->fire($task, ['payload' => true]));
    }

    public function testFirePassesSubjectAndDataToListener(): void
    {
        $task = 'testFirePassesSubjectAndDataToListener';
        $data = ['payload' => true];
        $manager = new Manager();
        $captured = [];
        $manager->attach(
            $this->events->getEventsPrefix() . ':' . $task,
            function (EventInterface $event, object $subject, array $payload) use (&$captured): void {
                $captured = [
                    'type' => $event->getType(),
                    'source' => $event->getSource(),
                    'subject' => $subject,
                    'payload' => $payload,
                ];
            }
        );

        $this->events->setEventsManager($manager);
        $this->events->fire($task, $data);

        $this->assertSame($task, $captured['type']);
        $this->assertSame($this->events, $captured['source']);
        $this->assertSame($this->events, $captured['subject']);
        $this->assertSame($data, $captured['payload']);
    }
    
    public function testFireCancelException(): void
    {
        $task = 'testFireCancelException';
        $manager = new Manager();
        $manager->attach($this->events->getEventsPrefix() . ':' . $task, function ($event) {
            $event->stop();
            return 'first-return';
        }, 1);
        $this->events->setEventsManager($manager);
        $this->expectException(\Phalcon\Events\Exception::class);
        $this->expectExceptionMessageMatches('/Trying to cancel a non-cancelable event/');
        $result = $this->events->fire($task, [], false);
    }

    public function testFireRejectsMissingEventsManagerService(): void
    {
        $events = new class {
            use EventsAwareTrait;

            public function getEventsManager(): ?ManagerInterface
            {
                return null;
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(ManagerInterface::class);

        $events->fire('missingManager');
    }
}
