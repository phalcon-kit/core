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

namespace PhalconKit\Mvc\Controller\Behavior\Query;

use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

/**
 * Resets request/configured pagination offset after offset initialization.
 *
 * Attach this listener when an action should always start at the first result
 * while preserving the rest of the controller's pagination/query state.
 */
class RemoveOffset
{
    /**
     * Reset the controller offset to the first row.
     *
     * @param Event $event Controller lifecycle event emitted after offset initialization.
     * @param Restful $controller REST controller whose offset should be reset.
     * @return void
     */
    public function afterInitializeOffset(Event $event, Restful $controller): void
    {
        $controller->setOffset(0);
    }
}
