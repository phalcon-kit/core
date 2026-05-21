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

use PhalconKit\Support\Slug;
use PhalconKit\Tests\Unit\AbstractUnit;

class SlugTest extends AbstractUnit
{
    public function testSlugify(): void
    {
        $tests = [
            'should-remain-the-same' => 'should-remain-the-same',
            'slugify-value' => 'Slugify Value',
            'another-slugify-value' => 'Another Slugify Value',
            'complex-slugify-value-with-some-special-characters' => 'Complex Slugify Value with some special characters!',
            'slugify-value-with-numbers123' => 'Slugify Value with Numbers123',
            'slugify-value-with-diacritics' => 'Slúgîfy Válúe wîth Díacrîtícs',
            'slugify-value-with-hyphen' => 'Slugify - Value with - hyphen',
            'slugify-value-with-space' => ' Slugify Value with space ',
            'slugify-value-with-multiple-spaces' => 'Slugify    Value with multiple   spaces',
        ];
        
        foreach ($tests as $expected => $value) {
            $this->assertEquals($expected, Slug::generate($value));
        }
        
        $this->assertEquals('changed.delimiter', Slug::generate('Changed Delimiter', [], '.'));
    }

    public function testGenerateAppliesCustomReplacementBeforeCleaning(): void
    {
        $this->assertSame('rock-and-tea', Slug::generate('Rock & Tea', [
            '&' => 'and',
        ]));

        $this->assertSame('after_replace', Slug::generate('Before Replace', [
            'Before' => 'After',
        ], '_'));
    }

    public function testCleanStringNormalizesRepeatedSeparatorsWithCustomDelimiter(): void
    {
        $this->assertSame(
            'alpha.beta.gamma',
            Slug::cleanString(' Alpha   Beta / Gamma ', '.')
        );
    }

    public function testCleanStringReturnsEmptyStringForEmptyOrSymbolOnlyInput(): void
    {
        $this->assertSame('', Slug::cleanString('', '-'));
        $this->assertSame('', Slug::cleanString('!!!', '-'));
    }

    public function testCleanStringReturnsEmptyStringForInvalidUtf8Input(): void
    {
        $this->assertSame('', Slug::cleanString("\xff", '-'));
    }
}
