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

namespace PhalconKit\Mvc\Controller\Traits\Abstracts\Query\Fields;

use Phalcon\Support\Collection;

/**
 * Abstract contract for list/detail exposure field policies.
 */
trait AbstractExposeFields
{
    /**
     * Initialize exposure field policy.
     */
    abstract public function initializeExposeFields(): void;
    
    /**
     * Replace exposure field policy.
     */
    abstract public function setExposeFields(?Collection $exposeFields): void;
    
    /**
     * Return exposure field policy.
     */
    abstract public function getExposeFields(): ?Collection;
}
