<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Events;

use Phalcon\Di\Di;
use Phalcon\Contracts\Events\Manager as EventsManagerContract;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Support\Helper;
use PhalconKit\Support\Slug;

/**
 * The EventsAwareTrait provides methods for managing events within a class.
 */
trait EventsAwareTrait
{
    /**
     * Event prefix to use as a component
     * my-component:beforeSomeTask
     * my-component:afterSomeTask
     */
    public static ?string $eventsPrefix;
    
    /**
     * The event manager responsible for handling and triggering events.
     */
    protected ?EventsManagerContract $eventsManager;
    
    /**
     * Set the events manager
     */
    public function setEventsManager(EventsManagerContract $manager): void
    {
        $this->eventsManager = $manager;
    }
    
    /**
     * Get the events manager.
     */
    public function getEventsManager(): ?EventsManagerContract
    {
        $this->eventsManager ??= Di::getDefault()->get('eventsManager');
        return $this->eventsManager;
    }
    
    
    /**
     * Get the event component prefix
     *
     * @return string|null The event component prefix, or null if not set
     */
    public static function getEventsPrefix(): ?string
    {
        self::$eventsPrefix ??= Helper::slugify(
            Helper::uncamelize(
                basename(
                    str_replace(
                        '\\',
                        '/',
                        __CLASS__
                    )
                )
            )
        );
        return self::$eventsPrefix;
    }
    
    /**
     * Sets the events prefix.
     *
     * @param string|null $eventsPrefix The prefix to be used for events. Pass null to remove the prefix.
     *
     * @return void
     */
    public static function setEventsPrefix(?string $eventsPrefix): void
    {
        self::$eventsPrefix = $eventsPrefix;
    }
    
    /**
     * Fire an event.
     *
     * @param string $task The task to execute.
     * @param mixed|null $data The optional data to pass to the event.
     * @param bool $cancelable Whether the event is cancelable or not. Defaults to false.
     *
     * @return mixed
     */
    public function fire(string $task, mixed $data = null, bool $cancelable = false): mixed
    {
        $eventType = $this->getEventsPrefix() . ':' . $task;
        $eventsManager = $this->getEventsManager();
        
        if (!$eventsManager instanceof EventsManagerContract) {
            throw new InvalidArgumentException(
                "Events manager must be an instance of '" . EventsManagerContract::class . "'."
            );
        }
        
        return $eventsManager->fire($eventType, $this, $data, $cancelable);
    }
}
