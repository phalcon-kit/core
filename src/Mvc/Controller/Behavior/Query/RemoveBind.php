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
 * Clears all prepared bind values after REST bind initialization.
 *
 * Attach this listener to an action when configured/request-derived bind
 * values should be ignored while leaving the rest of the query pipeline
 * enabled.
 */
class RemoveBind
{
    /**
     * Remove every bind value from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after bind initialization.
     * @param Restful $controller REST controller whose bind collection should be cleared.
     * @return void
     */
    public function afterInitializeBind(Event $event, Restful $controller): void
    {
        $controller->getBind()?->clear();
    }
}
