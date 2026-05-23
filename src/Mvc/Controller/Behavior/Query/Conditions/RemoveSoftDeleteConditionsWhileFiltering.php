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
use Phalcon\Filter\Exception as FilterException;
use PhalconKit\Mvc\Controller\Restful;

class RemoveSoftDeleteConditionsWhileFiltering
{
    /**
     * This method is executed after initializing conditions for the given controller.
     * Clears the soft delete conditions for the controller if specific filter fields are provided.
     *
     * @param Event $event The event instance triggering the method.
     * @param Restful $controller The controller instance on which conditions are being initialized.
     * @return void
     * @throws FilterException When request parameter filtering fails.
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        if ($controller->hasFiltersFieldsParams(['deleted'])) {
            $controller->getSoftDeleteConditions()?->clear();
        }
    }
}
