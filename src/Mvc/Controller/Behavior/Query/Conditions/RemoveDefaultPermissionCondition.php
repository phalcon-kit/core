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
 * Removes only the default permission condition after initialization.
 *
 * Use this for actions that replace the framework-generated permission
 * predicate with custom permission conditions or another explicit access rule.
 */
class RemoveDefaultPermissionCondition
{
    /**
     * Remove the `default` permission condition entry from the controller.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose permission conditions should be adjusted.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getPermissionConditions()?->remove('default');
    }
}
