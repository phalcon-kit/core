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

trait SaveFields
{
    /**
     * Controller-owned save-field policy.
     *
     * Null delegates writable-field decisions to Phalcon's normal model assign
     * behavior. A non-null collection is passed to `ModelInterface::assign()`
     * so REST payloads can write only explicitly configured fields.
     */
    protected ?Collection $saveFields = null;
    
    /**
     * Initialize the writable field list for REST save/create/update actions.
     *
     * Concrete controllers should override this method and call
     * {@see setSaveFields()} when a resource needs mass-assignment protection
     * at the controller layer. The default is null for backward compatibility.
     */
    public function initializeSaveFields(): void
    {
        $this->setSaveFields(null);
    }
    
    /**
     * Replace the fields clients may write through REST persistence actions.
     *
     * Passing null leaves assign unrestricted. Passing an empty collection makes
     * the policy explicit but gives `assign()` no allowed fields, which is a
     * useful closed default for read-only resources.
     */
    public function setSaveFields(array|Collection|null $saveFields): void
    {
        $this->saveFields = CollectionPolicy::normalizeNullable($saveFields);
    }
    
    /**
     * Return the configured save-field policy.
     *
     * The save query trait converts the collection to an array and passes it to
     * Phalcon's model assignment API together with the optional map-field
     * policy.
     */
    public function getSaveFields(): ?Collection
    {
        return $this->saveFields;
    }

    /**
     * Check whether save-field configuration is present.
     *
     * This reports policy presence only. An empty collection still means the
     * controller intentionally configured a closed writable-field policy.
     */
    public function hasSaveFields(): bool
    {
        return $this->saveFields !== null;
    }

    /**
     * Merge additional save-field entries into the current policy.
     *
     * Merge semantics are centralized in {@see CollectionPolicy}: null starts
     * from the incoming collection, empty incoming collections leave an existing
     * policy unchanged, and associative keys can override previous entries.
     */
    public function mergeSaveFields(array|Collection $saveFields): void
    {
        $this->saveFields = CollectionPolicy::mergeNullable(
            $this->saveFields,
            CollectionPolicy::normalize($saveFields)
        );
    }
}
