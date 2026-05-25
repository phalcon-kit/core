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
 * Behavior flag that disables HAVING-clause initialization.
 *
 * Use this with aggregate/grouped endpoints that need full control over their
 * HAVING predicates instead of the REST controller defaults.
 */
class SkipHaving
{
    /**
     * Tell the REST controller to skip HAVING collection initialization.
     *
     * @return false Always disables HAVING query state for the action.
     */
    public function getHaving(): bool
    {
        return false;
    }
}
