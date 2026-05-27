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

use PhalconKit\Support\Options\Manager;
use PhalconKit\Support\Options\ManagerInterface;
use PhalconKit\Tests\Unit\AbstractUnit;

class ManagerTest extends AbstractUnit
{
    public ManagerInterface $manager;
    
    public function testConstruct(): void
    {
        $options = ['test' => 'test', 'nesting' => ['test' => 'nested']];
        $this->manager = new Manager($options);
        
        // test get all options
        $this->assertSame($options, $this->manager->getOptions());
        
        // test default option
        $this->assertEquals('test', $this->manager->get('test'));
        $this->assertTrue($this->manager->has('test'));
        $this->assertFalse($this->manager->has('non-existing-key'));
        $this->assertEquals('default', $this->manager->get('non-existing-key', 'default'));
        
        // test changed option
        $this->manager->set('test', 'changed');
        $this->assertEquals('changed', $this->manager->get('test'));
        
        // reset options (should be the original options)
        $this->manager->reset();
        $this->assertEquals($options, $this->manager->getOptions());
        
        // re-initialize options
        $newOptions = ['new' => 'test'];
        $this->manager->initializeOptions($newOptions);
        $this->assertSame($newOptions, $this->manager->getOptions());
        
        // old options should not exist and should be null
        $this->assertNull($this->manager->get('test'));
        
        // clear options
        $this->manager->clear();
        $this->assertEquals([], $this->manager->getOptions());
        
        // reset options (should be the reinitialized options)
        $this->manager->reset();
        $this->assertEquals($newOptions, $this->manager->getOptions());
        
        // remove option
        $this->manager->remove('new');
        $this->assertNull($this->manager->get('new'));
        $this->assertEquals([], $this->manager->getOptions());
    }

    public function testSetOptionsCanMergeWithExistingOptions(): void
    {
        $this->manager = new Manager([
            'first' => 'original',
            'second' => 'kept',
        ]);

        $this->manager->setOptions([
            'first' => 'changed',
            'third' => 'added',
        ], true);

        $this->assertSame([
            'first' => 'changed',
            'second' => 'kept',
            'third' => 'added',
        ], $this->manager->getOptions());
    }

    public function testNullOptionUsesDefaultAndIsNotReportedAsPresent(): void
    {
        $this->manager = new Manager();
        $this->manager->set('nullable');

        $this->assertFalse($this->manager->has('nullable'));
        $this->assertSame('fallback', $this->manager->get('nullable', 'fallback'));
        $this->assertArrayHasKey('nullable', $this->manager->getOptions());
    }

    public function testRemoveDeletesNullValuedOptions(): void
    {
        $this->manager = new Manager([
            'nullable' => null,
            'kept' => 'value',
        ]);

        $this->manager->remove('nullable');

        $this->assertSame([
            'kept' => 'value',
        ], $this->manager->getOptions());
    }
}
