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
 * Abstract contract for configured PHQL join definitions.
 */
trait AbstractJoins
{
    /**
     * Initialize join definitions.
     */
    abstract public function initializeJoins(): void;
    
    /**
     * Replace join definitions.
     */
    abstract public function setJoins(?Collection $joins): void;
    
    /**
     * Return join definitions.
     */
    abstract public function getJoins(): ?Collection;
}
