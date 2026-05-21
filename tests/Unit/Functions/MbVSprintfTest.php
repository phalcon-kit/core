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

namespace PhalconKit\Tests\Unit\Functions;

use PhalconKit\Tests\Unit\AbstractUnit;

class MbVSprintfTest extends AbstractUnit
{
    protected function setUp(): void
    {
        /**
         * This setup method is intentionally left empty.
         * This test class does not require any specific initialization or fixtures.
         */
    }
    
    public function testMbSprintfBasic(): void
    {
        $this->assertEquals('苹果', mb_sprintf('%s', '苹果'));
    }
    
    public function testMbSprintfBasicMultibyte(): void
    {
        $this->assertEquals('こんにちは, 世界', mb_sprintf('%s, %s', 'こんにちは', '世界'));
    }
    
    public function testMbSprintfMixedStrings(): void
    {
        $this->assertEquals('Hello, 世界', mb_sprintf('%s, %s', 'Hello', '世界'));
    }
    
    public function testMbSprintfDifferentArgumentTypes(): void
    {
        $this->assertEquals('1, 2.50, word', mb_sprintf('%d, %.2f, %s', 1, 2.5, 'word'));
    }
    
    public function testMbSprintfMultibyteInFormat(): void
    {
        $this->assertEquals('これはテストです', mb_sprintf('これは%sです', 'テスト'));
    }
    
    public function testMbSprintfWidthAndPrecision(): void
    {
        $this->assertEquals('        世界', mb_sprintf('%10s', '世界'));
        $this->assertEquals('12.35', mb_sprintf('%.2f', 12.3456));
        $this->assertEquals('abc', mb_sprintf('%.3s', 'abcdef'));
    }
    
    public function testMbSprintfPaddingAndAlignment(): void
    {
        $this->assertEquals('世界        ', mb_sprintf('%-10s', '世界'));
    }
    
    public function testMbSprintfComplexFormat(): void
    {
        $this->assertEquals('001: Word', mb_sprintf('%03d: %s', 1, 'Word'));
    }
    
    public function testMbSprintfManyArguments(): void
    {
        $format = join(' ', array_fill(0, 10, '%s'));
        $args = array_fill(0, 10, 'word');
        $expected = join(' ', $args);
        $this->assertEquals($expected, mb_sprintf($format, ...$args));
    }
    
    public function testMbSprintfInvalidFormat(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessageMatches('/Unknown format specifier/');
        $this->assertFalse(mb_sprintf('%q', 'test')); // Assuming the function returns false on error
    }
    
    public function testMbSprintfEmptyStrings(): void
    {
        $this->assertEquals('', mb_sprintf('', ''));
    }
    
    public function testMbVsprintfBasicMultibyte(): void
    {
        $format = '%s, %s';
        $args = ['こんにちは', '世界'];
        $this->assertEquals('こんにちは, 世界', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfMixedStrings(): void
    {
        $format = '%s, %s';
        $args = ['Hello', '世界'];
        $this->assertEquals('Hello, 世界', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfDifferentArgumentTypes(): void
    {
        $format = '%d, %.2f, %s';
        $args = [1, 2.5, 'word'];
        $this->assertEquals('1, 2.50, word', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfMultibyteInFormat(): void
    {
        $format = 'これは%sです';
        $args = ['テスト'];
        $this->assertEquals('これはテストです', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfWidthAndPrecision(): void
    {
        $format = '%10s, %.2f';
        $args = ['世界', 12.3456];
        $this->assertEquals('        世界, 12.35', mb_vsprintf($format, $args));
        $this->assertEquals('こん', mb_vsprintf('%.2s', ['こんにちは']));
    }

    public function testMbVsprintfEscapedPercent(): void
    {
        $this->assertSame('Progress: 100%', mb_vsprintf('Progress: 100%%', []));
    }
    
    public function testMbVsprintfPaddingAndAlignment(): void
    {
        $format = '%-10s';
        $args = ['世界'];
        $this->assertEquals('世界        ', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfComplexFormat(): void
    {
        $format = '%03d: %s';
        $args = [1, 'Word'];
        $this->assertEquals('001: Word', mb_vsprintf($format, $args));
    }
    
    public function testMbVsprintfEmptyStrings(): void
    {
        $this->assertEquals('', mb_vsprintf('', []));
    }
    
    public function testMbVsprintfInvalidFormat(): void
    {
        $this->expectException(\ValueError::class);
        $this->expectExceptionMessageMatches('/Unknown format specifier/');
        $this->assertFalse(mb_vsprintf('%q', ['test'])); // Assuming the function returns false on error
    }
    
    public function testMbVsprintfManyArguments(): void
    {
        $format = join(' ', array_fill(0, 10, '%s'));
        $args = array_fill(0, 10, 'word');
        $expected = join(' ', $args);
        $this->assertEquals($expected, mb_vsprintf($format, $args));
    }
}
