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
 * Behavior flag that disables REST join initialization.
 *
 * Attach this when an action should not inherit configured joins, dynamic
 * joins, or relation joins from the controller query policy.
 */
class SkipJoins
{
    /**
     * Tell the REST controller to skip join collection initialization.
     *
     * @return false Always disables join query state for the action.
     */
    public function getJoins(): bool
    {
        return false;
    }
}
