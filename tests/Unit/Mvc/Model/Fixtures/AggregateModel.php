<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Core event wrappers over the same table used by the native comparison. */
final class AggregateModel extends \PhalconKit\Mvc\Model
{
    public function initialize(): void
    {
        $this->setSource('aggregate_values');
    }
}
