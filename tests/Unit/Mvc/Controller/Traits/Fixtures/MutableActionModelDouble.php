<?php

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Controller\Traits\Fixtures;

use PhalconKit\Mvc\Model\Behavior\Position as PositionBehavior;
use PhalconKit\Mvc\Model\Behavior\SoftDelete;
use PhalconKit\Mvc\Model\Interfaces\PositionInterface;
use PhalconKit\Mvc\Model\Interfaces\SoftDeleteInterface;

final class MutableActionModelDouble extends QueryModelDouble implements PositionInterface, SoftDeleteInterface
{
    public bool $deleteResult = true;

    public bool $restoreResult = true;

    public bool $reorderResult = true;

    public ?int $reorderedPosition = null;

    /**
     * Return the configured delete result without touching persistence.
     */
    public function delete(): bool
    {
        return $this->deleteResult;
    }

    /**
     * Disable real soft-delete behavior setup for action response tests.
     */
    public function initializeSoftDelete(?array $options = null): void
    {
    }

    /**
     * Accept soft-delete behavior assignment to satisfy the public contract.
     */
    public function setSoftDeleteBehavior(SoftDelete $softDeleteBehavior): void
    {
    }

    /**
     * This focused double does not need a real soft-delete behavior instance.
     */
    public function getSoftDeleteBehavior(): SoftDelete
    {
        throw new \LogicException('No soft-delete behavior is configured for this test double.');
    }

    /**
     * No-op used to satisfy the soft-delete interface.
     */
    public function disableSoftDelete(): void
    {
    }

    /**
     * No-op used to satisfy the soft-delete interface.
     */
    public function enableSoftDelete(): void
    {
    }

    /**
     * This test double always behaves as an entity eligible for restoration.
     */
    public function isDeleted(?string $field = null, ?int $deletedValue = null): bool
    {
        return true;
    }

    /**
     * Return the configured restore result without touching persistence.
     */
    public function restore(?string $field = null, ?int $notDeletedValue = null): bool
    {
        return $this->restoreResult;
    }

    /**
     * Disable real position behavior setup for action response tests.
     */
    public function initializePosition(?array $options = null): void
    {
    }

    /**
     * Accept position behavior assignment to satisfy the public contract.
     */
    public function setPositionBehavior(PositionBehavior $positionBehavior): void
    {
    }

    /**
     * This focused double does not need a real position behavior instance.
     */
    public function getPositionBehavior(): PositionBehavior
    {
        throw new \LogicException('No position behavior is configured for this test double.');
    }

    /**
     * Return the configured reorder result and capture the requested position.
     */
    public function reorder(?int $position = null, ?string $positionField = null): bool
    {
        $this->reorderedPosition = $position;

        return $this->reorderResult;
    }
}
