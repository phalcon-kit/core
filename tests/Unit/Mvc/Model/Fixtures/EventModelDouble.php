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

use Phalcon\Mvc\Model\MetaDataInterface;
use PhalconKit\Mvc\Model;

class EventModelDouble extends Model
{
    public static array $cancelEvents = [];
    public static array $firedEvents = [];

    public ?MetaDataInterface $fakeModelsMetaData = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource('event_model_double');
    }

    #[\Override]
    public function fireEventCancel(string $eventName): bool
    {
        self::$firedEvents[] = $eventName;
        return !in_array($eventName, self::$cancelEvents, true);
    }

    #[\Override]
    public function fireEvent(string $eventName): bool
    {
        self::$firedEvents[] = $eventName;
        return true;
    }

    #[\Override]
    public function getModelsMetaData(): MetaDataInterface
    {
        return $this->fakeModelsMetaData ?? parent::getModelsMetaData();
    }

    public static function resetEvents(): void
    {
        self::$cancelEvents = [];
        self::$firedEvents = [];
    }
}
