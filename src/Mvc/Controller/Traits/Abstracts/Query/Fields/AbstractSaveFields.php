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
 */
trait AbstractSaveFields
{
    /**
     * Initialize save field policy.
     */
    abstract public function initializeSaveFields(): void;
    
    /**
     * Replace save field policy.
     */
    abstract public function setSaveFields(?Collection $saveFields): void;
    
    /**
     * Return save field policy.
     */
    abstract public function getSaveFields(): ?Collection;
}
