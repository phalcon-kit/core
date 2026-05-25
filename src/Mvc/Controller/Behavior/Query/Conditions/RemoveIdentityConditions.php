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
 * Clears all identity-scope conditions after condition initialization.
 *
 * Use this only for actions that intentionally bypass identity-derived query
 * scoping and enforce access through another explicit policy.
 */
class RemoveIdentityConditions
{
    /**
     * Remove every identity condition from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose identity conditions should be cleared.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getIdentityConditions()?->clear();
    }
}
