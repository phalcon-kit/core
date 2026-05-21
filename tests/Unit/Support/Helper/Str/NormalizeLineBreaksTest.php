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

namespace PhalconKit\Tests\Unit\Support\Helper\Str;

use PhalconKit\Support\Helper\Str\NormalizeLineBreaks;
use PhalconKit\Tests\Unit\AbstractUnit;

class NormalizeLineBreaksTest extends AbstractUnit
{
    public function testInvokeNormalizesWindowsAndClassicMacLineBreaks(): void
    {
        $normalize = new NormalizeLineBreaks();

        $this->assertSame(
            "first\nsecond\nthird\nfourth",
            $normalize("first\r\nsecond\rthird\nfourth")
        );
    }

    public function testInvokeSupportsCustomPatternAndReplacement(): void
    {
        $normalize = new NormalizeLineBreaks();

        $this->assertSame(
            'first|second|third',
            $normalize("first\r\nsecond\nthird", "/\r\n|\n/", '|')
        );
    }

    public function testInvokeReturnsInputWhenPatternIsEmpty(): void
    {
        $normalize = new NormalizeLineBreaks();
        $input = "first\r\nsecond";

        $this->assertSame($input, $normalize($input, ''));
    }
}
