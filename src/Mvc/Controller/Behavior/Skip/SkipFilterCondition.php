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
 * Behavior flag that disables request-filter condition initialization.
 *
 * Use this for endpoints where request filter parameters should not generate
 * SQL/PHQL conditions automatically.
 */
class SkipFilterCondition
{
    /**
     * Tell the REST controller to skip filter condition initialization.
     *
     * @return false Always disables request-filter conditions for the action.
     */
    public function getFilterCondition(): bool
    {
        return false;
    }
}
