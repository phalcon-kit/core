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

namespace PhalconKit\Mvc\Controller\Behavior\Skip;

use Phalcon\Events\Event;
use PhalconKit\Mvc\Controller\Restful;

/**
 * Removes the default soft-delete condition after condition assembly.
 *
 * This legacy skip behavior is still useful in permission config that predates
 * the newer query-condition remover classes. It keeps the broader condition
 * pipeline active while removing only the `softDelete` group from the combined
 * condition collection.
 */
class SkipSoftDeleteCondition
{
    /**
     * Remove the combined soft-delete condition group from the controller.
     *
     * @param Event $event Controller lifecycle event emitted after conditions are initialized.
     * @param Restful $controller REST controller whose combined conditions should be adjusted.
     * @return void
     */
    public function afterConditions(Event $event, Restful $controller): void
    {
        $controller->getConditions()?->remove('softDelete');
    }
}
