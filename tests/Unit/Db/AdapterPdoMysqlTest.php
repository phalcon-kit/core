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

use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use PhalconKit\Db\Adapter\Pdo\Mysql;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Db\Fixtures\FakePdo;
use PhalconKit\Tests\Unit\Db\Fixtures\FakePdoStatement;

class AdapterPdoMysqlTest extends AbstractUnit
{
    public function testRewriteQueryPlaceholdersDuplicatesRepeatedNamedParameters(): void
    {
        $adapter = $this->createAdapter();

        [$sql, $bind, $bindTypes] = $adapter->rewriteQueryPlaceholders(
            'SELECT :id, :name, :id, :id, :colon',
            [
                'id' => 42,
                ':name' => 'Ada',
                'colon' => 'kept',
            ],
            [
                'id' => \PDO::PARAM_INT,
                ':name' => \PDO::PARAM_STR,
                'colon' => \PDO::PARAM_STR,
            ]
        );

        $this->assertSame('SELECT :id, :name, :id_2, :id_3, :colon', $sql);
        $this->assertSame([
            'id' => 42,
            ':name' => 'Ada',
            'colon' => 'kept',
            'id_2' => 42,
            'id_3' => 42,
        ], $bind);
        $this->assertSame([
            'id' => \PDO::PARAM_INT,
            ':name' => \PDO::PARAM_STR,
            'colon' => \PDO::PARAM_STR,
            'id_2' => \PDO::PARAM_INT,
            'id_3' => \PDO::PARAM_INT,
        ], $bindTypes);
    }

    public function testExecutePreparedUsesRewrittenStatementAndBindValues(): void
    {
        $adapter = $this->createAdapter(new FakePdo());
        $statement = FakePdoStatement::create('SELECT :id + :id');

        $result = $adapter->executePrepared(
            $statement,
            ['id' => 2],
            ['id' => \PDO::PARAM_INT]
        );

        $this->assertInstanceOf(FakePdoStatement::class, $result);
        $this->assertSame('SELECT :id + :id_2', $result->queryString);
        $this->assertTrue($result->executed);
        $this->assertSame([
            'id' => [2, \PDO::PARAM_INT],
            'id_2' => [2, \PDO::PARAM_INT],
        ], $result->boundValues);
    }

    public function testExecutePreparedWrapsExecutionErrorsWithDebugContext(): void
    {
        $adapter = $this->createAdapter(new FakePdo(true));
        $statement = FakePdoStatement::create('SELECT :id + :id');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('execute failed - SELECT :id + :id_2 - {"id":2,"id_2":2}');

        $adapter->executePrepared(
            $statement,
            ['id' => 2],
            ['id' => \PDO::PARAM_INT]
        );
    }

    private function createAdapter(?FakePdo $pdo = null): Mysql
    {
        $adapter = (new \ReflectionClass(Mysql::class))->newInstanceWithoutConstructor();
        assert($adapter instanceof Mysql);

        if ($pdo) {
            $pdoProperty = (new \ReflectionClass(AbstractPdo::class))->getProperty('pdo');
            $pdoProperty->setValue($adapter, $pdo);
        }

        return $adapter;
    }
}
