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
use PHPUnit\Framework\Attributes\DataProvider;

class CollectionPolicyTest extends AbstractUnit
{
    #[DataProvider('enabledValueProvider')]
    public function testIsEnabledValueNormalizesBooleanLikeMapValues(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, CollectionPolicy::isEnabledValue($value));
    }

    /**
     * @return iterable<string, array{mixed, bool}>
     */
    public static function enabledValueProvider(): iterable
    {
        yield 'null' => [null, false];
        yield 'false' => [false, false];
        yield 'zero int' => [0, false];
        yield 'zero float' => [0.0, false];
        yield 'empty string' => ['', false];
        yield 'blank string' => ['   ', false];
        yield 'zero string' => ['0', false];
        yield 'false string' => ['false', false];
        yield 'no string' => ['no', false];
        yield 'off string' => ['off', false];
        yield 'true' => [true, true];
        yield 'one int' => [1, true];
        yield 'one float' => [1.0, true];
        yield 'true string' => ['true', true];
        yield 'yes string' => ['yes', true];
        yield 'on string' => ['on', true];
        yield 'arbitrary string' => ['enabled', true];
        yield 'array' => [['enabled'], true];
    }

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

    public function testMergeNullableAppendsNumericValuesWithoutMutatingInputs(): void
    {
        $base = new Collection([
            'id',
        ]);
        $incoming = new Collection([
            'label',
        ]);

        $merged = CollectionPolicy::mergeNullable($base, $incoming);

        $this->assertSame(['id'], $base->toArray());
        $this->assertSame(['label'], $incoming->toArray());
        $this->assertSame([
            'id',
            'label',
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

    public function testIntersectNullablePreservesDuplicateBaseValuesAndReindexes(): void
    {
        $base = new Collection([
            'id',
            'id',
            'label',
            'status',
        ]);
        $incoming = new Collection([
            'id',
            'status',
        ]);

        $intersected = CollectionPolicy::intersectNullable($base, $incoming);

        $this->assertSame([
            'id',
            'id',
            'status',
        ], $intersected->toArray());
    }
}
