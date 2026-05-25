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
 * Removes the default soft-delete condition only when the request filters by deletion state.
 *
 * This lets endpoints keep normal "not deleted" behavior by default, while
 * allowing explicit `deleted` filters to include deleted rows or select only
 * deleted rows according to the request filter value.
 */
class RemoveDefaultSoftDeleteConditionWhileFiltering
{
    /**
     * Drop the `default` soft-delete condition when a `deleted` filter is present.
     *
     * @param Event $event Controller lifecycle event emitted after condition initialization.
     * @param Restful $controller REST controller whose soft-delete conditions should be adjusted.
     * @return void
     * @throws FilterException When reading or sanitizing request filter parameters fails.
     */
    public function afterInitializeConditions(Event $event, Restful $controller): void
    {
        if ($controller->hasFiltersFieldsParams(['deleted'])) {
            $controller->getSoftDeleteConditions()?->remove('default');
        }
    }
}
