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
 * Abstract contract for public-field to model-field mapping.
 */
trait AbstractMapFields
{
    /**
     * Initialize field mapping policy.
     */
    abstract public function initializeMapFields(): void;
    
    /**
     * Replace field mapping policy.
     */
    abstract public function setMapFields(?Collection $mapFields): void;
    
    /**
     * Return field mapping policy.
     */
    abstract public function getMapFields(): ?Collection;
}
