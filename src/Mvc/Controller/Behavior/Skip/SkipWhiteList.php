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
 * Behavior flag that disables whitelist initialization.
 *
 * Attach this when an action must not apply the controller's configured field
 * whitelist while normalizing request data or query state.
 */
class SkipWhiteList
{
    /**
     * Tell the REST controller to skip whitelist initialization.
     *
     * @return false Always disables whitelist state for the action.
     */
    public function getWhiteList(): bool
    {
        return false;
    }
}
