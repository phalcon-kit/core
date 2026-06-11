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

trait ExposeFields
{
    /**
     * Controller-owned response exposure policy.
     *
     * Null lets the exposer use its default behavior for the current item. A
     * non-null collection constrains the fields or nested relation paths that
     * standard REST responses expose to clients.
     */
    protected ?Collection $exposeFields = null;
    
    /**
     * Initialize the response exposure field list.
     *
     * Concrete controllers can override this method and call
     * {@see setExposeFields()} to define the public response shape for standard
     * REST actions. The default remains null for backward compatibility.
     */
    public function initializeExposeFields(): void
    {
        $this->setExposeFields(null);
    }
    
    /**
     * Replace the fields standard REST actions may expose.
     *
     * Passing null leaves exposure unrestricted/defaulted. Passing an empty
     * collection is a closed response policy and can be useful when a custom
     * transformer owns the complete payload.
     */
    public function setExposeFields(array|Collection|null $exposeFields): void
    {
        $this->exposeFields = CollectionPolicy::normalizeNullable($exposeFields);
    }
    
    /**
     * Return the configured response exposure policy.
     *
     * The expose trait converts this collection to an array when listing or
     * exposing records for standard REST responses.
     */
    public function getExposeFields(): ?Collection
    {
        return $this->exposeFields;
    }

    /**
     * Check whether response exposure configuration is present.
     *
     * This reports policy presence only. An empty collection still means the
     * controller explicitly configured exposure.
     */
    public function hasExposeFields(): bool
    {
        return $this->exposeFields !== null;
    }

    /**
     * Merge additional response exposure entries into the current policy.
     *
     * Merge semantics are centralized in {@see CollectionPolicy}: null starts
     * from the incoming collection, empty incoming collections leave an existing
     * policy unchanged, and associative keys can override previous entries.
     */
    public function mergeExposeFields(array|Collection $exposeFields): void
    {
        $this->exposeFields = CollectionPolicy::mergeNullable(
            $this->exposeFields,
            CollectionPolicy::normalize($exposeFields)
        );
    }
}
