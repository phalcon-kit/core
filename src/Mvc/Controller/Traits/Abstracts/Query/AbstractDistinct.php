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
 * Abstract contract for SELECT DISTINCT configuration.
 */
trait AbstractDistinct
{
    /**
     * Initialize distinct-field configuration.
     */
    abstract public function initializeDistinct(): void;
    
    /**
     * Replace distinct-field configuration.
     */
    abstract public function setDistinct(array|Collection|null $distinct): void;
    
    /**
     * Return distinct-field configuration.
     */
    abstract public function getDistinct(): ?Collection;
}
