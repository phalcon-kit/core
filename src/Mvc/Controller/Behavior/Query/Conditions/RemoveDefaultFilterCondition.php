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

namespace PhalconKit\Mvc\Controller\Behavior\Query\Conditions;

use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

/**
 * Removes only the default request-filter condition after condition initialization.
 *
 * This keeps custom filter conditions configured by the application while
 * suppressing the framework's default filter predicate for the action.
 */
class RemoveDefaultFilterCondition
{
    /**
     * Remove the `default` filter condition entry from the controller.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose filter conditions should be adjusted.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getFilterConditions()?->remove('default');
    }
}
