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

namespace PhalconKit\Mvc\Controller\Behavior\Query\Fields;

use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

/**
 * Clears exposed-field rules after REST field initialization.
 *
 * Attach this listener when an action should not apply the controller's
 * configured response-exposure field policy.
 */
class RemoveExposeFields
{
    /**
     * Remove every exposed-field rule from the controller field state.
     *
     * @param Event $event Controller lifecycle event emitted after field initialization.
     * @param Restful $controller REST controller whose exposed fields should be cleared.
     * @return void
     */
    public function afterInitializeFields(Event $event, Restful $controller): void
    {
        $controller->getExposeFields()?->clear();
    }
}
