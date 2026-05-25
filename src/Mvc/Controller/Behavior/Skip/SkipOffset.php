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
 * Behavior flag that disables offset initialization.
 *
 * Attach this when an action should ignore request/configured pagination
 * offsets and manage row positioning itself.
 */
class SkipOffset
{
    /**
     * Tell the REST controller to skip offset initialization.
     *
     * @return false Always disables offset query state for the action.
     */
    public function getOffset(): bool
    {
        return false;
    }
}
