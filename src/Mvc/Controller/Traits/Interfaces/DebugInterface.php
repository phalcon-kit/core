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

namespace PhalconKit\Mvc\Controller\Traits\Interfaces;

/**
 * Contract for controller debug-mode checks.
 */
interface DebugInterface
{
    /**
     * Determine whether debug output should be enabled for the current request.
     */
    public function isDebugEnabled(): bool;
}
