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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use PhalconKit\Mvc\Model\Traits\Events;

class EventsTraitSubject extends EventsTraitBase
{
    use Events;

    public static array $cancelEvents = [];
    public static array $firedEvents = [];

    public static function loadInstance(): static
    {
        return new static();
    }

    public function fireEventCancel(string $eventName): bool
    {
        self::$firedEvents[] = $eventName;
        return !in_array($eventName, self::$cancelEvents, true);
    }

    public function fireEvent(string $eventName): bool
    {
        self::$firedEvents[] = $eventName;
        return true;
    }

    public static function resetEvents(): void
    {
        self::$cancelEvents = [];
        self::$firedEvents = [];
    }
}
