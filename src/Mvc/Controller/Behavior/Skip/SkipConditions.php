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
 * Behavior flag that disables the combined REST condition collection.
 *
 * This bypasses condition assembly for actions that intentionally build their
 * own query or should run without controller-managed filtering constraints.
 */
class SkipConditions
{
    /**
     * Tell the REST controller to skip condition collection initialization.
     *
     * @return false Always disables the combined condition collection.
     */
    public function getConditions(): bool
    {
        return false;
    }
}
