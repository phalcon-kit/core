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

/**
 * Behavior flag that disables distinct-expression initialization.
 *
 * Attach this when an action must not inherit configured `DISTINCT` handling
 * from the REST query builder.
 */
class SkipDistinct
{
    /**
     * Tell the REST controller to skip distinct collection initialization.
     *
     * @return false Always disables distinct query state for the action.
     */
    public function getDistinct(): bool
    {
        return false;
    }
}
