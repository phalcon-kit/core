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

class SprintfnTest extends AbstractUnit
{
    protected function setUp(): void
    {
        /**
         * This setup method is intentionally left empty.
         * This test class does not require any specific initialization or fixtures.
         */
    }
    
    public function testSprintfnMultipleNamedArguments(): void
    {
        $format = 'Name: %name$s, Age: %age$d';
        $args = ['name' => 'Alice', 'age' => 30];
        $this->assertEquals('Name: Alice, Age: 30', sprintfn($format, $args));
    }
    
    public function testSprintfnArgumentReuse(): void
    {
        $format = 'Name: %name$s, Name Again: %name$s';
        $args = ['name' => 'Alice'];
        $this->assertEquals('Name: Alice, Name Again: Alice', sprintfn($format, $args));
    }
    
    public function testSprintfnUnmatchedArguments(): void
    {
        $format = 'Name: %name$s';
        $args = ['name' => 'Alice', 'unused' => 'Value'];
        $this->assertEquals('Name: Alice', sprintfn($format, $args));
    }
    
    public function testSprintfnMissingArguments(): void
    {
        $format = 'Name: %name$s, Age: %age$d, Gender: %gender$s';
        ;
        $args = ['name' => 'Alice']; // Missing 'age'
        
        $this->setErrorHandler();
        $this->expectException(\Exception::class);
        $this->assertFalse(sprintfn($format, $args));
    }

    public function testSprintfnReturnsFalseForMissingArgumentsWhenWarningIsSuppressed(): void
    {
        $this->assertFalse(@sprintfn('Name: %name$s, Age: %age$d', ['name' => 'Alice']));
    }
    
    public function testSprintfnSpecialCharacterHandling(): void
    {
        $format = 'Data: %special_char$s';
        $args = ['special_char' => '$pecial@Character!'];
        $this->assertEquals('Data: $pecial@Character!', sprintfn($format, $args));
    }
    
    public function testSprintfnEmptyFormatString(): void
    {
        $this->assertEquals('', sprintfn('', []));
    }
    
    public function testSprintfnComplexFormats(): void
    {
        $format = 'Name: %-10s | Age: %04d | Salary: %.2f';
        $args = ['name' => 'Alice', 'age' => 30, 'salary' => 12345.678];
        $this->assertEquals('Name: Alice      | Age: 0030 | Salary: 12345.68', sprintfn($format, $args));
    }
}
