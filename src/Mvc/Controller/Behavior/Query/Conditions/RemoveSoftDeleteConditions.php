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
 * Clears all soft-delete conditions after condition initialization.
 *
 * Attach this when an action needs full control over deleted-row visibility and
 * should remove both default and application-provided soft-delete predicates.
 */
class RemoveSoftDeleteConditions
{
    /**
     * Remove every soft-delete condition from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose soft-delete conditions should be cleared.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getSoftDeleteConditions()?->clear();
    }
}
