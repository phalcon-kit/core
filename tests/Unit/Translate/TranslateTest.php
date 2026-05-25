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

namespace PhalconKit\Tests\Unit\Translate;

use Phalcon\Translate\Adapter\AdapterInterface;
use Phalcon\Translate\InterpolatorFactory;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Translate\Adapter\NestedNativeArray;

class TranslateTest extends AbstractUnit
{
    private ?AdapterInterface $translate;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->translate = $this->di->get('translate');
    }
    
    public function testTranslateInstanceFromDi(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->translate);
    }
    
    public function testNonExistingKey(): void
    {
        $key = 'non-existing-key';
        $this->assertFalse($this->translate->has($key));
        $this->assertFalse($this->translate->exists($key));
        $this->assertEquals($key, $this->translate->query($key));
        $this->assertEquals($key, $this->translate->t($key));
    }
    
    public function testExistingKey(): void
    {
        $key = 'Phalcon Kit';
        $this->assertTrue($this->translate->has($key));
        $this->assertTrue($this->translate->exists($key));
        $this->assertNotSame($key, $this->translate->query($key));
        $this->assertNotSame($key, $this->translate->t($key));
    }

    public function testNestedNativeArray(): void
    {
        $translates = [
            'test' => 'value',
            'test.nesting' => 'value',
            'nesting.collision' => 'value',
            'nesting' => [
                'test' => 'value',
                'interpolation' => 'value:%value%',
                'collision' => 'should-not-work',
                'collision2' => 'should-not-work',
                'with.nesting' => 'value',
                'with.sub' => [
                    'nesting' => 'value'
                ],
            ],
            'nesting.collision2' => 'value'
        ];
        
        // initialize nested native array
        $interpolatorFactory = new InterpolatorFactory();
        $nestedNativeArray = new NestedNativeArray($interpolatorFactory, [
            'content' => $translates,
        ]);
        
        // tests
        $tests = [
            'test' => 'value',
            'test.nesting' => 'value',
            'nesting.test' => 'value',
            'nesting.interpolation' => 'value:interpolated',
            'nesting.collision' => 'value',
            'nesting.collision2' => 'value',
        ];
        $placeholders = [
            'value' => 'interpolated'
        ];
        foreach ($tests as $key => $value) {
            $this->assertEquals($value, $nestedNativeArray->query($key, $placeholders));
            $this->assertEquals($value, $nestedNativeArray->t($key, $placeholders));
            $this->assertTrue($nestedNativeArray->has($key));
            $this->assertTrue($nestedNativeArray->exists($key));
        }
        
        // non-existing
        $key = 'non-existing';
        $this->assertEquals($key, $nestedNativeArray->query($key));
        $this->assertEquals($key, $nestedNativeArray->t($key));
        $this->assertEquals($key, $nestedNativeArray->notFound($key));
        $this->assertFalse($nestedNativeArray->has($key));
        $this->assertFalse($nestedNativeArray->exists($key));
    }

    public function testNestedNativeArraySupportsCustomDelimiter(): void
    {
        $translator = new NestedNativeArray(new InterpolatorFactory(), [
            'delimiter' => '::',
            'content' => [
                'button' => [
                    'save' => 'Save',
                ],
            ],
        ]);

        $this->assertTrue($translator->has('button::save'));
        $this->assertTrue($translator->exists('button::save'));
        $this->assertSame('Save', $translator->query('button::save'));
        $this->assertSame('Save', $translator->t('button::save'));
    }

    public function testNestedNativeArrayReturnsOriginalContent(): void
    {
        $content = [
            'hello' => 'Hello',
            'nested' => [
                'value' => 'Value',
            ],
        ];
        $translator = new NestedNativeArray(new InterpolatorFactory(), [
            'content' => $content,
        ]);

        $this->assertSame($content, $translator->toArray());
    }

    public function testNestedNativeArrayCanThrowWhenMissingKeyIsConfiguredAsError(): void
    {
        $translator = new NestedNativeArray(new InterpolatorFactory(), [
            'triggerError' => true,
            'content' => [],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot find translation key: missing.key');

        $translator->query('missing.key');
    }
}
