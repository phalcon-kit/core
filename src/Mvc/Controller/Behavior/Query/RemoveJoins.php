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
 * Clears configured and dynamic joins after join initialization.
 *
 * Use this listener for actions that should query only the root model while
 * leaving other controller query state, such as filters or limits, available.
 */
class RemoveJoins
{
    /**
     * Remove every join definition from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after join initialization.
     * @param Restful $controller REST controller whose join collection should be cleared.
     * @return void
     */
    public function afterInitializeJoins(Event $event, Restful $controller): void
    {
        $controller->getJoins()?->clear();
    }
}
