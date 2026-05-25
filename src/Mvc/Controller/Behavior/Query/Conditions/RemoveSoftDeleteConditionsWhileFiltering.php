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

/**
 * Clears all soft-delete conditions only when the request filters by deletion state.
 *
 * This variant is stronger than {@see RemoveDefaultSoftDeleteConditionWhileFiltering}:
 * when a `deleted` filter is present, it removes every soft-delete predicate so
 * the explicit request filter owns deleted-row visibility.
 */
class RemoveSoftDeleteConditionsWhileFiltering
{
    /**
     * Clear all soft-delete conditions when a `deleted` filter is present.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose soft-delete conditions should be cleared.
     * @return void
     * @throws FilterException When reading or sanitizing request filter parameters fails.
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        if ($controller->hasFiltersFieldsParams(['deleted'])) {
            $controller->getSoftDeleteConditions()?->clear();
        }
    }
}
