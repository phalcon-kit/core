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

namespace PhalconKit\Tests\Unit\Events\Fixtures;

use Phalcon\Events\Event;

class ConfiguredDisabledListener
{
    public function beforeRun(Event $event, object $source, mixed $data = null): void
    {
        ConfiguredEventListenerState::$calls[] = [
            'listener' => static::class,
            'hasDi' => false,
            'data' => $data,
        ];
    }
}
