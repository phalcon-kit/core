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
 * Behavior flag that disables selected-column initialization.
 *
 * Use this when an action should rely on the model/default query projection
 * instead of the REST controller's configured column collection.
 */
class SkipColumns
{
    /**
     * Tell the REST controller to skip column collection initialization.
     *
     * @return false Always disables configured column selection for the action.
     */
    public function getColumns(): bool
    {
        return false;
    }
}
