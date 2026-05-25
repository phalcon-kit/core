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
 * Abstract contract for REST query offset configuration.
 */
trait AbstractOffset
{
    /**
     * Initialize offset configuration.
     */
    abstract public function initializeOffset(): void;
    
    /**
     * Replace the requested offset.
     */
    abstract public function setOffset(?int $offset): void;
    
    /**
     * Return the requested offset.
     */
    abstract public function getOffset(): ?int;
    
    /**
     * Return the default offset.
     */
    abstract public function defaultOffset(): ?int;
}
