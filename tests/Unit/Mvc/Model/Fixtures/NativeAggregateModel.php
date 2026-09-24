<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

/** Native Phalcon reference for database-driver aggregate values and types. */
final class NativeAggregateModel extends \Phalcon\Mvc\Model
{
    public function initialize(): void
    {
        $this->setSource('aggregate_values');
    }
}
