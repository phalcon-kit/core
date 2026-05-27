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
 *
 * Map fields let REST controllers accept stable public payload names while
 * assigning different model attributes. Null disables mapping and leaves
 * payload keys unchanged.
 */
trait AbstractMapFields
{
    /**
     * Initialize the assignment field-map policy.
     */
    abstract public function initializeMapFields(): void;
    
    /**
     * Replace the assignment field-map policy.
     *
     * @param Collection|null $mapFields Field map collection or null to disable
     *     assignment mapping.
     */
    abstract public function setMapFields(?Collection $mapFields): void;
    
    /**
     * Return the configured assignment field-map policy.
     *
     * @return Collection|null Field map collection or null when mapping is
     *     disabled.
     */
    abstract public function getMapFields(): ?Collection;
}
