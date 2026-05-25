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
 * Behavior flag that disables REST query bind-type initialization.
 *
 * This is useful together with {@see SkipBind} when an action should not pass
 * request/configured bind metadata into the compiled Phalcon query options.
 */
class SkipBindTypes
{
    /**
     * Tell the REST controller to skip bind-type collection initialization.
     *
     * @return false Always disables bind-type values for the action.
     */
    public function getBindTypes(): bool
    {
        return false;
    }
}
