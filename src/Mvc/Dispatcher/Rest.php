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
 *
 * @see https://docs.phalcon.io/5.13/dispatcher/
 */
class Rest extends Injectable
{
    /**
     * Allow the dispatcher to continue before a REST controller is invoked.
     *
     * The native Phalcon dispatcher event signature is kept even though the
     * current implementation does not need the arguments. That makes this class
     * a safe place to add future REST dispatch behavior without changing the
     * listener's public shape.
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher): bool
    {
        return true;
    }
}
