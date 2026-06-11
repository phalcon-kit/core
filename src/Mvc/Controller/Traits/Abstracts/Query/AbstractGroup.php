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
 * Abstract contract for GROUP BY query configuration.
 */
trait AbstractGroup
{
    /**
     * Initialize group configuration.
     */
    abstract public function initializeGroup(): void;
    
    /**
     * Replace group configuration.
     */
    abstract public function setGroup(array|Collection|null $group): void;
    
    /**
     * Return group configuration.
     */
    abstract public function getGroup(): ?Collection;
    
    /**
     * Return the controller's default grouping policy.
     */
    abstract public function defaultGroup(): array|string|null;
}
