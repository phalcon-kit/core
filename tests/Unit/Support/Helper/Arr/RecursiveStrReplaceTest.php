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

use PhalconKit\Support\Helper\Arr\RecursiveStrReplace;
use PhalconKit\Tests\Unit\AbstractUnit;

class RecursiveStrReplaceTest extends AbstractUnit
{
    public function testInvokeReplacesStringsRecursively(): void
    {
        $replace = new RecursiveStrReplace();

        $result = $replace([
            'title' => 'Hello :name',
            'nested' => [
                'message' => ':name has :count items',
            ],
            'unchanged' => 42,
        ], [
            ':name' => 'Ada',
            ':count' => '3',
        ]);

        $this->assertSame([
            'title' => 'Hello Ada',
            'nested' => [
                'message' => 'Ada has 3 items',
            ],
            'unchanged' => 42,
        ], $result);
    }

    public function testProcessLeavesNonStringsUntouched(): void
    {
        $result = RecursiveStrReplace::process([
            'bool' => true,
            'null' => null,
            'list' => [1, 2],
        ], [
            '1' => 'one',
        ]);

        $this->assertSame([
            'bool' => true,
            'null' => null,
            'list' => [1, 2],
        ], $result);
    }

    public function testProcessWithEmptyReplacementMapLeavesStringsUntouched(): void
    {
        $result = RecursiveStrReplace::process([
            'message' => 'Hello :name',
            'nested' => [
                'message' => ':name has :count items',
            ],
        ], []);

        $this->assertSame([
            'message' => 'Hello :name',
            'nested' => [
                'message' => ':name has :count items',
            ],
        ], $result);
    }
}
