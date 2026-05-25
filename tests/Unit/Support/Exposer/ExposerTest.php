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

namespace PhalconKit\Tests\Unit\Support\Exposer;

use PhalconKit\Support\Exposer\Builder;
use PhalconKit\Support\Exposer\BuilderInterface;
use PhalconKit\Support\Exposer\Exposer;
use PhalconKit\Tests\Unit\AbstractUnit;

class ExposerTest extends AbstractUnit
{
    public function testBuilder(): void
    {
        $builder = new Builder();
        $tests = [
            null,
            true,
            false,
            0,
            1,
            '',
            '\\',
            ' spaces ' => 'spaces',
            ' spaces .' => 'spaces',
            'test',
            'test.test',
            'test. . . . test' => 'test.test',
            'test. . . . test .' => 'test.test',
            '.test. . . . test .' => 'test.test',
            '!@#$%^&*()',
            '!@#$%^&*().!@#$%^&*()_+',
            ['test'],
            ['test' => 'test'],
            (object)['test' => 'test'],
        ];
        
        foreach ($tests as $key => $value) {
            $test = $key;
            $expected = $value;
            if (is_int($key)) {
                $test = $value;
            }
            
            // value
            $builder->setValue($test);
            $this->assertEquals($test, $builder->getValue());
            
            // parent
            $builder->setParent($test);
            $this->assertEquals($test, $builder->getParent());
            
            if (is_string($test)) {
                // key
                $this->assertEquals($expected, Builder::processKey($test));
                
                $builder->setKey($test);
                $this->assertEquals($expected, $builder->getKey());
                
                // context key
                $builder->setContextKey($test);
                $this->assertEquals($expected, $builder->getContextKey());
                
                // full key
                $this->assertEquals(trim($expected . '.' . $expected, '.'), $builder->getFullKey());
            }
            
            if (is_bool($test)) {
                // expose
                $builder->setExpose($test);
                $this->assertEquals($test, $builder->getExpose());
                
                // protected
                $builder->setProtected($test);
                $this->assertEquals($test, $builder->getProtected());
            }
            
            // columns
            if (is_array($test) || is_null($test)) {
                $builder->setColumns($test);
                $this->assertEquals($test, $builder->getColumns());
            }
        }
        
        // Expose
        $builder->setExpose(true);
        $this->assertTrue($builder->getExpose());
        $builder->setExpose(false);
        $this->assertFalse($builder->getExpose());
        
        // Protected
        $builder->setProtected(true);
        $this->assertTrue($builder->getProtected());
        $builder->setProtected(false);
        $this->assertFalse($builder->getProtected());
    }
    
    public function testExposer(): void
    {
        $test = [
            'test_null' => null,
            'test_empty' => '',
            'test_int' => 0,
            'test_float' => 0.1,
            'test_true' => true,
            'test_false' => false,
            'test_string' => 'string',
            'test_empty_array' => [],
            'test_empty_object' => (object)[],
            'test_array' => ['test' => 'test'],
            'test_object' => (object)['test' => 'test'],
            'test_removed' => 'test_removed',
            'test_removed_two' => 'test_removed_two',
            'test_after_removed' => 'test_after_removed',
            'test_replace_value' => 'test_value_before',
            'test_same_value_mb_sprintf' => 'test_same_value_mb_sprintf',
            'test_altered_value_mb_sprintf' => 'test_altered_value_mb_sprintf',
        ];
        $expected = $test;
        $expected['test_empty_object'] = (array)$expected['test_empty_object'];
        $expected['test_object'] = (array)$expected['test_object'];
        
        $builder = Exposer::createBuilder($test);
        $actual = Exposer::expose($builder);
        $this->assertEquals($expected, $actual);
        
        // Apply transformation rules after proving the unmodified builder
        // output.
        unset($expected['test_removed']);
        unset($expected['test_removed_two']);
        $expected['test_replace_value'] = 'test_value_after';
        $expected['test_same_value_mb_sprintf'] = 'test_same_value_mb_sprintf';
        $expected['test_altered_value_mb_sprintf'] = 'test_altered_value_mb_sprintf!';
        $builder = Exposer::createBuilder($test, [
            true,
            'test_removed' => false,
            'test_removed_two' => false,
            'test_replace_value' => 'test_value_after',
            'new_value' => 'test',
            'test_get_value_sprint' => '%s',
            'test_altered_value_mb_sprintf' => '%s!',
        ]);
        $actual = Exposer::expose($builder);
        $this->assertEquals($expected, $actual);
        $this->assertArrayNotHasKey('new_value', $actual);
    }
    
    public function testNestedExpose(): void
    {
        $array = [
            'test' => 'test',
            'test_hidden' => 'hidden',
            'nested' => [
                [
                    'id' => 1,
                    'test' => 'test',
                    'test_hidden' => 'hidden',
                ],
                [
                    'id' => 2,
                    'test' => 'test',
                    'test_hidden' => 'hidden',
                ],
            ],
        ];
        
        $result = [
            'test' => 'test',
            'nested' => [
                [
                    'id' => 1,
                    'test' => 'test',
                ],
                [
                    'id' => 2,
                    'test' => 'test',
                ],
            ],
        ];
        
        $builder = Exposer::createBuilder($array, [
            false,
            'test',
            'nested' => [
                'id',
                'test',
            ],
        ]);
        $actual = Exposer::expose($builder);
        $this->assertEquals($result, $actual);
        
        $builder = Exposer::createBuilder($array, [
            true,
            'test' => true,
            'test_hidden' => false,
            'nested' => [
                false,
                'id',
                'test',
            ],
        ]);
        $actual = Exposer::expose($builder);
        $this->assertEquals($result, $actual);
    }

    public function testProtectedFieldsAreHiddenUnlessExplicitlyAllowed(): void
    {
        $data = [
            'name' => 'Ada',
            '_token' => 'secret',
        ];

        $builder = Exposer::createBuilder($data);

        $this->assertSame(['name' => 'Ada'], Exposer::expose($builder));

        $builder = Exposer::createBuilder($data, protected: true);

        $this->assertSame($data, Exposer::expose($builder));
    }

    public function testCallableRulesCanMutateFormatHideAndAddNestedRules(): void
    {
        $data = [
            'name' => 'ada',
            'nickname' => 'countess',
            'secret' => 'hidden',
            'profile' => [
                'email' => 'ada@example.test',
                'role' => 'admin',
            ],
        ];

        $builder = Exposer::createBuilder($data, [
            false,
            'name' => static function (Builder $builder): BuilderInterface {
                $builder->setValue(mb_strtoupper((string) $builder->getValue()));

                return $builder;
            },
            'nickname' => static fn (): string => 'Lady %s',
            'secret' => static fn (): bool => false,
            'profile' => static fn (): array => [
                'email' => true,
            ],
        ]);

        $this->assertSame([
            'name' => 'ADA',
            'nickname' => 'Lady countess',
            'profile' => [
                'email' => 'ada@example.test',
            ],
        ], Exposer::expose($builder));
    }

    public function testExposeCanUseObjectToArrayAndDenyRootWithoutColumns(): void
    {
        $object = new class {
            public function toArray(): array
            {
                return [
                    'id' => 123,
                    'label' => 'Example',
                ];
            }
        };

        $builder = Exposer::createBuilder($object, [
            false,
            'label',
        ]);

        $this->assertSame(['label' => 'Example'], Exposer::expose($builder));

        $builder = Exposer::createBuilder(['id' => 123], expose: false);

        $this->assertSame([], Exposer::expose($builder));
    }

    public function testParentCallableIterableRuleDoesNotCascadeDuplicateRules(): void
    {
        $builder = new Builder();
        $builder->setColumns([
            'profile' => static fn (): array => [
                'other' => true,
            ],
        ]);
        $builder->setExpose(true);
        $builder->setContextKey('profile');
        $builder->setValue([
            'name' => 'Ada',
        ]);

        $this->assertSame([
            'name' => 'Ada',
        ], Exposer::expose($builder));
    }

    public function testCallableUnsupportedReturnKeepsCurrentExposureState(): void
    {
        $builder = Exposer::createBuilder([
            'value' => 'kept',
        ], [
            false,
            'value' => static fn (): int => 123,
        ]);

        $this->assertSame(['value' => 'kept'], Exposer::expose($builder));
    }

    public function testParseColumnsSupportsBooleanKeysAndSkipsNonStringNumericValues(): void
    {
        $columns = static function (): \Generator {
            yield 0 => 123;
            yield true => false;
            yield 'name' => '';
        };

        $parsed = Exposer::parseColumnsRecursive($columns());

        $this->assertSame([
            '' => true,
            'name' => true,
        ], $parsed);
    }
}
