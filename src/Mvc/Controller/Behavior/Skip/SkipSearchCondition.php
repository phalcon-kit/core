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
 * Behavior flag that disables search condition initialization.
 *
 * Use this when request search parameters should not be converted into
 * controller-managed search predicates.
 */
class SkipSearchCondition
{
    /**
     * Tell the REST controller to skip search condition initialization.
     *
     * @return false Always disables search conditions for the action.
     */
    public function getSearchCondition(): bool
    {
        return false;
    }
}
