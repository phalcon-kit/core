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
 * Removes only the default identity-scope condition after initialization.
 *
 * Attach this when an action should not apply the framework-generated identity
 * predicate but should keep any custom identity conditions configured by the
 * application.
 */
class RemoveDefaultIdentityCondition
{
    /**
     * Remove the `default` identity condition entry from the controller.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose identity conditions should be adjusted.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getIdentityConditions()?->remove('default');
    }
}
