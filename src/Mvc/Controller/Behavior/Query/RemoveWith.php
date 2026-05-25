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
 * Clears eager-loading relation requests after `with` initialization.
 *
 * Use this listener when an action should not honor configured or request
 * eager-loading relation graphs.
 */
class RemoveWith
{
    /**
     * Remove every eager-loading relation from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after `with` initialization.
     * @param Restful $controller REST controller whose eager-loading collection should be cleared.
     * @return void
     */
    public function afterInitializeWith(Event $event, Restful $controller): void
    {
        $controller->getWith()?->clear();
    }
}
