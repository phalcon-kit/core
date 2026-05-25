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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query;

/**
 * Abstract contract for REST query limit policy.
 *
 * Implementations distinguish between a requested limit, a maximum allowed
 * limit, and the default values used when requests omit pagination options.
 */
trait AbstractLimit
{
    /**
     * Initialize the requested limit from controller parameters.
     */
    abstract public function initializeLimit(): void;
    
    /**
     * Replace the requested limit.
     */
    abstract public function setLimit(?int $limit): void;
    
    /**
     * Return the effective requested limit.
     */
    abstract public function getLimit(): ?int;
    
    /**
     * Replace the maximum allowed limit.
     */
    abstract public function setMaxLimit(?int $maxLimit): void;
    
    /**
     * Return the maximum allowed limit.
     */
    abstract public function getMaxLimit(): ?int;
    
    /**
     * Return the default requested limit.
     */
    abstract public function defaultLimit(): ?int;
    
    /**
     * Return the default maximum allowed limit.
     */
    abstract public function defaultMaxLimit(): ?int;
}
