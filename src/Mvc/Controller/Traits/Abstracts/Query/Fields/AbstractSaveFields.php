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
 * Abstract contract for fields that may be assigned during save operations.
 *
 * Save fields protect mass assignment at the REST controller layer. Null keeps
 * Phalcon model assignment unrestricted, while an empty collection is an
 * explicit read-only/closed writable-field policy.
 */
trait AbstractSaveFields
{
    /**
     * Initialize the save-field policy for REST persistence actions.
     */
    abstract public function initializeSaveFields(): void;
    
    /**
     * Replace the save-field policy.
     *
     * @param array|Collection|null $saveFields Field policy collection, null for
     *     unrestricted assignment, or an empty collection for a closed policy.
     */
    abstract public function setSaveFields(array|Collection|null $saveFields): void;
    
    /**
     * Return the configured save-field policy.
     *
     * @return Collection|null Field policy collection or null for unrestricted
     *     assignment.
     */
    abstract public function getSaveFields(): ?Collection;
}
