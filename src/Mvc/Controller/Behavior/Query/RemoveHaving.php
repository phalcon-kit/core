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
 * Clears HAVING predicates after HAVING initialization.
 *
 * Attach this listener when aggregate/grouped endpoints need to drop
 * controller-managed HAVING clauses but keep the rest of the query pipeline.
 */
class RemoveHaving
{
    /**
     * Remove every HAVING predicate from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after HAVING initialization.
     * @param Restful $controller REST controller whose HAVING collection should be cleared.
     * @return void
     */
    public function afterInitializeHaving(Event $event, Restful $controller): void
    {
        $controller->getHaving()?->clear();
    }
}
