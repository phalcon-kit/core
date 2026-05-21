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

namespace PhalconKit\Tests\Unit\Db;

use Phalcon\Db\Column;
use PhalconKit\Db\Dialect\Mysql;
use PhalconKit\Tests\Unit\AbstractUnit;

class DialectMysqlTest extends AbstractUnit
{
    public function testCustomFunctionsRenderMysqlExpressions(): void
    {
        $dialect = new Mysql();

        $this->assertSame(
            ' `name` REGEXP ^Ada',
            $dialect->getSqlExpression($this->functionExpression('regexp', [
                ['type' => 'qualified', 'name' => 'name'],
                ['type' => 'literal', 'value' => '^Ada'],
            ]))
        );
        $this->assertSame(
            ' ST_Distance_Sphere(point_a, point_b)',
            $dialect->getSqlExpression($this->functionExpression('ST_Distance_Sphere', [
                ['type' => 'literal', 'value' => 'point_a'],
                ['type' => 'literal', 'value' => 'point_b'],
            ]))
        );
        $this->assertSame(
            ' point(longitude, latitude)',
            $dialect->getSqlExpression($this->functionExpression('point', [
                ['type' => 'literal', 'value' => 'longitude'],
                ['type' => 'literal', 'value' => 'latitude'],
            ]))
        );
    }

    public function testRegisterMethodsCanBeCalledRepeatedly(): void
    {
        $dialect = new Mysql();

        $dialect->registerRegexpFunction();
        $dialect->registerDistanceSphereFunction();
        $dialect->registerPointFunction();

        $this->assertSame(
            ' `name` REGEXP ^Ada',
            $dialect->getSqlExpression($this->functionExpression('regexp', [
                ['type' => 'qualified', 'name' => 'name'],
                ['type' => 'literal', 'value' => '^Ada'],
            ]))
        );
    }

    public function testColumnDefinitionHandlesBinaryVarbinaryAndParentDefinitions(): void
    {
        $dialect = new Mysql();

        $this->assertSame(
            'INT(0)',
            $dialect->getColumnDefinition(new Column('id', ['type' => Column::TYPE_INTEGER]))
        );
        $this->assertSame(
            'BINARY(16)',
            $dialect->getColumnDefinition(new Column('hash', [
                'type' => Column::TYPE_BINARY,
                'size' => 16,
            ]))
        );
        $this->assertSame(
            'BINARY',
            $dialect->getColumnDefinition(new Column('hash', [
                'type' => Column::TYPE_BINARY,
            ]))
        );
        $this->assertSame(
            'VARBINARY(32)',
            $dialect->getColumnDefinition(new Column('hash', [
                'type' => Column::TYPE_VARBINARY,
                'size' => 32,
            ]))
        );
        $this->assertSame(
            'VARBINARY',
            $dialect->getColumnDefinition(new Column('hash', [
                'type' => Column::TYPE_VARBINARY,
            ]))
        );
        $this->assertSame(
            '',
            $dialect->getColumnDefinition(new Column('unknown', ['type' => 999]))
        );
    }

    private function functionExpression(string $name, array $arguments): array
    {
        return [
            'type' => 'functionCall',
            'name' => $name,
            'arguments' => $arguments,
        ];
    }
}
