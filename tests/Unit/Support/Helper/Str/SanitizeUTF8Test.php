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

use PhalconKit\Support\Helper\Str\SanitizeUTF8;
use PhalconKit\Tests\Unit\AbstractUnit;

class SanitizeUTF8Test extends AbstractUnit
{
    public function testInvokeRemovesInvalidCharactersAfterEncodingDetection(): void
    {
        $sanitize = new SanitizeUTF8();

        $this->assertSame('abcdef', $sanitize("abc\x00\x1Fdef"));
    }

    public function testInvokeConvertsIso88591InputToUtf8(): void
    {
        $sanitize = new SanitizeUTF8();
        $input = mb_convert_encoding("Caf\xC3\xA9", 'ISO-8859-1', 'UTF-8');

        $this->assertSame("Caf\xC3\xA9", $sanitize($input));
    }

    public function testInvokeSupportsCustomInvalidCharacterPattern(): void
    {
        $sanitize = new SanitizeUTF8();

        $this->assertSame('abc', $sanitize('abc123', '[0-9]'));
    }
}
