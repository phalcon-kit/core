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

namespace PhalconKit\Tests\Unit\Support\Helper\Arr;

use PhalconKit\Support\Helper\Arr\FlattenKeys;
use PhalconKit\Tests\Unit\AbstractUnit;

class FlattenKeysTest extends AbstractUnit
{
    public function testInvokeFlattensNestedKeysAndLowercasesByDefault(): void
    {
        $flattenKeys = new FlattenKeys();

        $this->assertSame([
            'foo.bar' => true,
            'foo.baz' => 'value',
            'foo' => false,
            'enabled' => true,
        ], $flattenKeys([
            'Foo' => [
                'Bar',
                'Baz' => 'value',
            ],
            'Enabled',
        ]));
    }

    public function testInvokeReturnsEmptyArrayForEmptyInput(): void
    {
        $flattenKeys = new FlattenKeys();

        $this->assertSame([], $flattenKeys());
        $this->assertSame([], FlattenKeys::process([]));
    }

    public function testProcessPreservesKeyCaseWhenRequested(): void
    {
        $this->assertSame([
            'Foo/Bar' => true,
            'Foo/Baz' => false,
            'Foo' => false,
        ], FlattenKeys::process([
            'Foo' => [
                'Bar',
                'Baz' => false,
            ],
        ], '/', false));
    }

    public function testProcessExecutesCallableValues(): void
    {
        $this->assertSame([
            'root.computed' => 'computed-value',
            'root.nested.enabled' => true,
            'root.nested' => false,
            'root' => false,
        ], FlattenKeys::process([
            'root' => [
                'computed' => static fn (): string => 'computed-value',
                'nested' => static fn (): array => [
                    'enabled',
                ],
            ],
        ]));
    }

    public function testProcessTreatsEmptyStringValuesAsEnabledFlags(): void
    {
        $this->assertSame([
            'emptyStringFlag' => true,
        ], FlattenKeys::process([
            'emptyStringFlag' => '',
        ]));
    }
}
