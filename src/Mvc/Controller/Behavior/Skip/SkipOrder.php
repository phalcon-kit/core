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
 * Behavior flag that disables order-by initialization.
 *
 * Use this for actions that need deterministic ordering outside the standard
 * REST order policy or must avoid request-driven sorting.
 */
class SkipOrder
{
    /**
     * Tell the REST controller to skip order collection initialization.
     *
     * @return false Always disables order-by query state for the action.
     */
    public function getOrder(): bool
    {
        return false;
    }
}
