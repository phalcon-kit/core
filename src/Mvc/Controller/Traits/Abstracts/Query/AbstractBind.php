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
 * Abstract contract for query bind values and bind types.
 */
trait AbstractBind
{
    /**
     * Initialize query bind values.
     */
    abstract public function initializeBind(): void;
    
    /**
     * Initialize query bind types.
     */
    abstract public function initializeBindTypes(): void;
    
    /**
     * Replace bind values used by compiled query options.
     */
    abstract public function setBind(?Collection $bind): void;
    
    /**
     * Return bind values used by compiled query options.
     */
    abstract public function getBind(): ?Collection;
    
    /**
     * Replace bind types used by compiled query options.
     */
    abstract public function setBindTypes(?Collection $bindTypes): void;
    
    /**
     * Return bind types used by compiled query options.
     */
    abstract public function getBindTypes(): ?Collection;
}
