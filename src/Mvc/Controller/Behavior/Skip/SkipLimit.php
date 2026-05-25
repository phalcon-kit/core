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
 * Behavior flag that disables limit initialization.
 *
 * Use this when an action should not receive controller/request pagination
 * limits before it prepares its final query.
 */
class SkipLimit
{
    /**
     * Tell the REST controller to skip limit initialization.
     *
     * @return false Always disables limit query state for the action.
     */
    public function getLimit(): bool
    {
        return false;
    }
}
