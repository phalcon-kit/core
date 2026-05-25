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
 * Resets the current limit to the configured maximum after limit initialization.
 *
 * This listener keeps the action bounded by the controller maximum while
 * discarding a narrower request/configured limit selected earlier in the
 * initialization pipeline.
 */
class RemoveLimit
{
    /**
     * Replace the current limit with the controller maximum limit.
     *
     * @param Event $event Controller lifecycle event emitted after limit initialization.
     * @param Restful $controller REST controller whose current limit should be reset.
     * @return void
     */
    public function afterInitializeLimit(Event $event, Restful $controller): void
    {
        $controller->setLimit($controller->getMaxLimit());
    }
}
