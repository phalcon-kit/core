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
 * Clears the combined condition collection after REST conditions are initialized.
 *
 * Attach this only when an action intentionally drops all controller-managed
 * predicates and supplies its own query constraints elsewhere.
 */
class RemoveConditions
{
    /**
     * Remove every combined condition group from the controller query state.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose condition collection should be cleared.
     * @return void
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        $controller->getConditions()?->clear();
    }
}
