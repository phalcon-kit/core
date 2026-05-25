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
 * Behavior flag that disables permission condition initialization.
 *
 * Attach this only when an action intentionally bypasses permission-derived
 * query predicates and enforces access through another explicit mechanism.
 */
class SkipPermissionCondition
{
    /**
     * Tell the REST controller to skip permission condition initialization.
     *
     * @return false Always disables permission conditions for the action.
     */
    public function getPermissionConditions(): bool
    {
        return false;
    }
}
