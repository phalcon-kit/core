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

namespace PhalconKit\Tests\Unit\Support\Options;

use PhalconKit\Support\Options\Options;
use PhalconKit\Support\Options\OptionsInterface;
use PhalconKit\Tests\Unit\AbstractUnit;

class OptionsTest extends AbstractUnit
{
    public OptionsInterface $options;
    
    public function testConstruct(): void
    {
        $options = ['test' => 'test', 'nesting' => ['test' => 'nested']];
        $this->options = new class ($options) implements OptionsInterface {
            use Options;
        };
        
        // test get all options
        $this->assertSame($options, $this->options->getOptions());
        
        // test default option
        $this->assertEquals('test', $this->options->getOption('test'));
        $this->assertTrue($this->options->hasOption('test'));
        $this->assertFalse($this->options->hasOption('non-existing-key'));
        $this->assertEquals('default', $this->options->getOption('non-existing-key', 'default'));
        
        // test changed option
        $this->options->setOption('test', 'changed');
        $this->assertEquals('changed', $this->options->getOption('test'));
        
        // reset options (should be the original options)
        $this->options->resetOptions();
        $this->assertEquals($options, $this->options->getOptions());
        
        // re-initialize options
        $newOptions = ['new' => 'test'];
        $this->options->initializeOptions($newOptions);
        $this->assertSame($newOptions, $this->options->getOptions());
        
        // old options should not exist and should be null
        $this->assertNull($this->options->getOption('test'));
        
        // clear options
        $this->options->clearOptions();
        $this->assertEquals([], $this->options->getOptions());
        
        // reset options (should be the reinitialized options)
        $this->options->resetOptions();
        $this->assertEquals($newOptions, $this->options->getOptions());
        
        // remove option
        $this->options->removeOption('new');
        $this->assertNull($this->options->getOption('new'));
        $this->assertEquals([], $this->options->getOptions());
    }

    public function testSetOptionsCanMergeWithExistingOptions(): void
    {
        $this->options = new class ([
            'first' => 'original',
            'second' => 'kept',
        ]) implements OptionsInterface {
            use Options;
        };

        $this->options->setOptions([
            'first' => 'changed',
            'third' => 'added',
        ], true);

        $this->assertSame([
            'first' => 'changed',
            'second' => 'kept',
            'third' => 'added',
        ], $this->options->getOptions());
    }

    public function testSetOptionsWithoutMergeReplacesExistingOptionsAndResetRestoresDefaults(): void
    {
        $defaults = [
            'first' => 'original',
            'nested' => [
                'default' => true,
            ],
        ];
        $this->options = new class ($defaults) implements OptionsInterface {
            use Options;
        };

        $this->options->setOptions([
            'second' => 'replacement',
        ]);

        $this->assertSame([
            'second' => 'replacement',
        ], $this->options->getOptions());

        $this->options->resetOptions();

        $this->assertSame($defaults, $this->options->getOptions());
    }

    public function testSetOptionsMergeIsShallowForNestedArrays(): void
    {
        $this->options = new class ([
            'nested' => [
                'default' => true,
                'keptOnlyBeforeMerge' => true,
            ],
            'kept' => 'value',
        ]) implements OptionsInterface {
            use Options;
        };

        $this->options->setOptions([
            'nested' => [
                'replacement' => true,
            ],
        ], true);

        $this->assertSame([
            'nested' => [
                'replacement' => true,
            ],
            'kept' => 'value',
        ], $this->options->getOptions());
    }

    public function testSetOptionMergeKeepsOtherOptions(): void
    {
        $this->options = new class ([
            'first' => 'original',
            'second' => 'kept',
        ]) implements OptionsInterface {
            use Options;
        };

        $this->options->setOption('first', 'changed', true);

        $this->assertSame([
            'first' => 'changed',
            'second' => 'kept',
        ], $this->options->getOptions());
    }

    public function testNullOptionsUseDefaultsButCanBeRemoved(): void
    {
        $this->options = new class ([
            'nullable' => null,
            'kept' => 'value',
        ]) implements OptionsInterface {
            use Options;
        };

        $this->assertFalse($this->options->hasOption('nullable'));
        $this->assertSame('fallback', $this->options->getOption('nullable', 'fallback'));
        $this->assertArrayHasKey('nullable', $this->options->getOptions());

        $this->options->removeOption('nullable');

        $this->assertSame([
            'kept' => 'value',
        ], $this->options->getOptions());

        $this->options->resetOptions();

        $this->assertArrayHasKey('nullable', $this->options->getOptions());
        $this->assertFalse($this->options->hasOption('nullable'));
        $this->assertSame('fallback', $this->options->getOption('nullable', 'fallback'));
    }
}
