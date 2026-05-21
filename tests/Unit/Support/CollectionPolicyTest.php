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

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Support\Collection;
use PhalconKit\Support\CollectionPolicy;
use PhalconKit\Tests\Unit\AbstractUnit;

class CollectionPolicyTest extends AbstractUnit
{
    public function testMergeNullableUsesIncomingWhenBaseIsNull(): void
    {
        $incoming = new Collection([
            'foo' => 'bar',
        ]);

        $merged = CollectionPolicy::mergeNullable(null, $incoming);

        $this->assertNotSame($incoming, $merged);
        $this->assertSame([
            'foo' => 'bar',
        ], $merged->toArray());
    }

    public function testMergeNullableKeepsBaseWhenIncomingIsEmpty(): void
    {
        $base = new Collection([
            'foo' => 'bar',
        ]);

        $merged = CollectionPolicy::mergeNullable($base, new Collection());

        $this->assertNotSame($base, $merged);
        $this->assertSame([
            'foo' => 'bar',
        ], $merged->toArray());
    }

    public function testMergeNullableOverlaysIncomingAssociativeKeys(): void
    {
        $base = new Collection([
            'foo' => 'bar',
            'keep' => 'base',
        ]);
        $incoming = new Collection([
            'foo' => 'baz',
            'new' => 'value',
        ]);

        $merged = CollectionPolicy::mergeNullable($base, $incoming);

        $this->assertSame([
            'foo' => 'baz',
            'keep' => 'base',
            'new' => 'value',
        ], $merged->toArray());
    }

    public function testIntersectNullableUsesIncomingWhenBaseIsNull(): void
    {
        $incoming = new Collection([
            'id',
            'label',
        ]);

        $intersected = CollectionPolicy::intersectNullable(null, $incoming);

        $this->assertNotSame($incoming, $intersected);
        $this->assertSame([
            'id',
            'label',
        ], $intersected->toArray());
    }

    public function testIntersectNullableReturnsSharedValuesOnly(): void
    {
        $base = new Collection([
            'id',
            'label',
            'status',
        ]);
        $incoming = new Collection([
            'label',
            'missing',
            'id',
        ]);

        $intersected = CollectionPolicy::intersectNullable($base, $incoming);

        $this->assertSame([
            'id',
            'label',
        ], $intersected->toArray());
    }
}
