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

namespace PhalconKit\Mvc\Controller\Traits\Query\Fields;

use Phalcon\Support\Collection;
use PhalconKit\Support\CollectionPolicy;

trait MapFields
{
    /**
     * Controller-owned public-to-model assignment map.
     *
     * Null disables assignment mapping and leaves payload keys unchanged. A
     * non-null collection is passed to Phalcon's assign API so controllers can
     * expose stable public field names while assigning different model fields.
     */
    protected ?Collection $mapFields = null;
    
    /**
     * Initialize the REST assignment field map.
     *
     * Concrete controllers can override this method and call
     * {@see setMapFields()} when public payload names differ from model
     * attribute names. The default is null so save behavior remains unchanged.
     */
    public function initializeMapFields(): void
    {
        $this->setMapFields(null);
    }
    
    /**
     * Replace the field map used by REST persistence actions.
     *
     * Passing null disables field mapping. Passing an empty collection keeps the
     * decision explicit but maps no payload keys.
     */
    public function setMapFields(array|Collection|null $mapFields): void
    {
        $this->mapFields = CollectionPolicy::normalizeNullable($mapFields);
    }
    
    /**
     * Return the configured assignment field map.
     *
     * The save query trait converts this collection to an array and passes it to
     * Phalcon's model assignment API together with the optional save-field
     * policy.
     */
    public function getMapFields(): ?Collection
    {
        return $this->mapFields;
    }

    /**
     * Check whether assignment mapping is configured.
     *
     * This reports policy presence only. An empty collection still means the
     * controller intentionally configured no field mappings.
     */
    public function hasMapFields(): bool
    {
        return $this->mapFields !== null;
    }

    /**
     * Merge additional assignment mappings into the current policy.
     *
     * Merge semantics are centralized in {@see CollectionPolicy}: null starts
     * from the incoming collection, empty incoming collections leave an existing
     * policy unchanged, and associative keys can override previous entries.
     */
    public function mergeMapFields(array|Collection $mapFields): void
    {
        $this->mapFields = CollectionPolicy::mergeNullable(
            $this->mapFields,
            CollectionPolicy::normalize($mapFields)
        );
    }
}
