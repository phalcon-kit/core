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

use Phalcon\Support\Collection;

/**
 * Abstract contract for ORDER BY query configuration.
 */
trait AbstractOrder
{
    /**
     * Initialize default order configuration.
     */
    abstract public function initializeDefaultOrder(): void;
    
    /**
     * Initialize request order configuration.
     */
    abstract public function initializeOrder(): void;
    
    /**
     * Replace request order configuration.
     */
    abstract public function setOrder(?Collection $order): void;
    
    /**
     * Return request order configuration.
     */
    abstract public function getOrder(): ?Collection;
    
    /**
     * Replace default order configuration.
     */
    abstract public function setDefaultOrder(array|string|null $defaultOrder): void;
    
    /**
     * Return default order configuration.
     */
    abstract public function getDefaultOrder(): array|string|null;
}
