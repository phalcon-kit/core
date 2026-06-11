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
 * Abstract contract for HAVING query conditions.
 */
trait AbstractHaving
{
    /**
     * Initialize HAVING conditions.
     */
    abstract public function initializeHaving(): void;
    
    /**
     * Replace HAVING conditions.
     */
    abstract public function setHaving(array|Collection|null $having): void;
    
    /**
     * Return HAVING conditions.
     */
    abstract public function getHaving(): ?Collection;
}
