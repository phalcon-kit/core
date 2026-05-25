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
 * Behavior flag that disables REST query bind initialization for an action.
 *
 * Attach this behavior when an action must ignore request/configured bind
 * values and leave the final Phalcon find options without a `bind` entry.
 */
class SkipBind
{
    /**
     * Tell the REST controller to skip bind collection initialization.
     *
     * @return false Always disables bind values for the behavior-aware action.
     */
    public function getBind(): bool
    {
        return false;
    }
}
