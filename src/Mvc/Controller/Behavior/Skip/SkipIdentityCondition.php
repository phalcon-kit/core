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
 * Behavior flag that disables identity-scope condition initialization.
 *
 * Attach this only for actions that deliberately bypass authenticated identity
 * scoping, such as public resources or framework-maintained internal queries.
 */
class SkipIdentityCondition
{
    /**
     * Tell the REST controller to skip identity condition initialization.
     *
     * @return false Always disables identity-scope conditions for the action.
     */
    public function getIdentityCondition(): bool
    {
        return false;
    }
}
