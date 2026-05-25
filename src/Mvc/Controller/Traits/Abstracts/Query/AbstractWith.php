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
 * Abstract contract for eager-loading relation configuration.
 */
trait AbstractWith
{
    /**
     * Initialize eager-loading relation configuration.
     */
    abstract public function initializeWith(): void;
    
    /**
     * Replace eager-loading relation configuration.
     */
    abstract public function setWith(?Collection $with): void;
    
    /**
     * Return eager-loading relation configuration.
     */
    abstract public function getWith(): ?Collection;
}
