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
 * Clears group-by expressions after group initialization.
 *
 * Use this listener when an action needs to ignore configured or request-driven
 * grouping while keeping other controller query state intact.
 */
class RemoveGroup
{
    /**
     * Remove every group expression from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after group initialization.
     * @param Restful $controller REST controller whose group collection should be cleared.
     * @return void
     */
    public function afterInitializeGroup(Event $event, Restful $controller): void
    {
        $controller->getGroup()?->clear();
    }
}
