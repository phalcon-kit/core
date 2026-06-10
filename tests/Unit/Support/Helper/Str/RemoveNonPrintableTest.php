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

use PhalconKit\Support\Helper\Str\RemoveNonPrintable;
use PhalconKit\Tests\Unit\AbstractUnit;

class RemoveNonPrintableTest extends AbstractUnit
{
    public function testInvokeRemovesControlCharacters(): void
    {
        $remove = new RemoveNonPrintable();

        $this->assertSame('abcdef', $remove("abc\x00\x1Fdef"));
    }

    public function testInvokePreservesPrintableUtf8Characters(): void
    {
        $remove = new RemoveNonPrintable();

        $this->assertSame("Caf\xC3\xA9 123", $remove("Caf\xC3\xA9 123"));
    }

    public function testInvokeSupportsCustomReplacement(): void
    {
        $remove = new RemoveNonPrintable();

        $this->assertSame('abc--def', $remove("abc\r\ndef", '[[:cntrl:]]', '-'));
    }
}
