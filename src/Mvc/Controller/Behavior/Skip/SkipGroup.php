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
 * Behavior flag that disables group-by initialization.
 *
 * Attach this when an action must not carry configured or request-derived
 * grouping into the final model query.
 */
class SkipGroup
{
    /**
     * Tell the REST controller to skip group collection initialization.
     *
     * @return false Always disables group-by query state for the action.
     */
    public function getGroup(): bool
    {
        return false;
    }
}
