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
 * Abstract contract for REST order-field policy configuration.
 *
 * Order fields are separate from filter/search fields because a field can be
 * safe to filter while still being too expensive, unstable, or semantically
 * wrong to expose as a client-controlled sort key.
 */
trait AbstractOrderFields
{
    /**
     * Initialize the order-field allow-list for the current request.
     */
    abstract public function initializeOrderFields(): void;

    /**
     * Replace the order-field policy.
     *
     * @param Collection|null $orderFields Field policy collection, null for
     *     unrestricted ordering, or an empty collection for a closed policy.
     */
    abstract public function setOrderFields(?Collection $orderFields): void;

    /**
     * Return the configured order-field policy.
     *
     * @return Collection|null Field policy collection or null for unrestricted
     *     ordering.
     */
    abstract public function getOrderFields(): ?Collection;

    /**
     * Check whether an order-field policy has been configured.
     */
    abstract public function hasOrderFields(): bool;

    /**
     * Merge additional order-field policy entries into the current policy.
     *
     * @param Collection $orderFields Additional field policy entries.
     */
    abstract public function mergeOrderFields(Collection $orderFields): void;
}
