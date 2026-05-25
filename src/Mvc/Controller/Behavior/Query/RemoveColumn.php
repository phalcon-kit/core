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
 * Clears configured query columns after column initialization.
 *
 * Use this listener when an action should fall back to the model/default
 * projection instead of controller-level selected or calculated columns.
 */
class RemoveColumn
{
    /**
     * Remove every configured column from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after column initialization.
     * @param Restful $controller REST controller whose column collection should be cleared.
     * @return void
     */
    public function afterInitializeColumn(Event $event, Restful $controller): void
    {
        $controller->getColumn()?->clear();
    }
}
