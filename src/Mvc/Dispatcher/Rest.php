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

namespace PhalconKit\Mvc\Dispatcher;

use Phalcon\Events\Event;
use Phalcon\Mvc\Dispatcher;
use PhalconKit\Di\Injectable;

/**
 * Pass-through dispatcher listener reserved for REST dispatch customization.
 *
 * The class is intentionally inert until REST-specific dispatch behavior is
 * promoted to a supported framework contract.
 */
class Rest extends Injectable
{
//    public function beforeDispatch(Event $event, Dispatcher $dispatcher): bool
    public function beforeDispatch(): bool
    {
        return true;
    }
}
