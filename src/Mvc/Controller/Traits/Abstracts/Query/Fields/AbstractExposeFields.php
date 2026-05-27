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
 *
 * Exposure fields shape standard REST responses. Null preserves the current
 * exposer default, while a non-null collection explicitly controls which
 * fields or nested paths may be serialized.
 */
trait AbstractExposeFields
{
    /**
     * Initialize the exposure-field policy for standard REST responses.
     */
    abstract public function initializeExposeFields(): void;
    
    /**
     * Replace the exposure-field policy.
     *
     * @param Collection|null $exposeFields Field policy collection, null for
     *     default exposure behavior, or an empty collection for a closed
     *     response policy.
     */
    abstract public function setExposeFields(?Collection $exposeFields): void;
    
    /**
     * Return the configured exposure-field policy.
     *
     * @return Collection|null Field policy collection or null for default
     *     exposure behavior.
     */
    abstract public function getExposeFields(): ?Collection;
}
