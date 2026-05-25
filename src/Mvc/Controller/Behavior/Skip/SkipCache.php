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
 * Behavior flag that disables REST query cache-option initialization.
 *
 * Attach this to actions that must bypass controller-level cache configuration
 * and let the model query run without generated cache options.
 */
class SkipCache
{
    /**
     * Tell the REST controller to skip cache configuration initialization.
     *
     * @return false Always disables cache options for the action.
     */
    public function getCache(): bool
    {
        return false;
    }
}
