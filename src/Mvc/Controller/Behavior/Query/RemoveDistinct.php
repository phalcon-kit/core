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
 * Clears distinct query expressions after distinct initialization.
 *
 * Attach this listener when an action must not inherit configured distinct
 * projection state from the controller query policy.
 */
class RemoveDistinct
{
    /**
     * Remove every distinct expression from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after distinct initialization.
     * @param Restful $controller REST controller whose distinct collection should be cleared.
     * @return void
     */
    public function afterInitializeDistinct(Event $event, Restful $controller): void
    {
        $controller->getDistinct()?->clear();
    }
}
