<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Migrations;

use Phalcon\Db\Adapter\AbstractAdapter;
use Phalcon\Db\Adapter\Pdo\Mysql;
use PhalconKit\Exception\RuntimeException;
use PhalconKit\Migrations\SqlMigration;
use PHPUnit\Framework\TestCase;

/** Verify SQL file batches fail predictably before or during database execution. */
final class SqlMigrationTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/phalconkit-sql-' . bin2hex(random_bytes(8));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testSqlIsExecutedVerbatimAndInTheSuppliedOrder(): void
    {
        $statements = ["-- A semicolon inside a literal is not a separator.\nSELECT 'a;b';\n", 'SELECT 2;'];
        $paths = [$this->sqlFile('first.sql', $statements[0]), $this->sqlFile('second.sql', $statements[1])];
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['execute'])->getMock();
        $executed = [];
        $db->expects(self::exactly(3))->method('execute')->willReturnCallback(static function (string $sql) use (&$executed): bool {
            $executed[] = $sql;
            return true;
        });
        $migration = $this->migration($db);
        $migration->runFiles($paths);
        $migration->runFile($paths[0]);
        self::assertSame([$statements[0], $statements[1], $statements[0]], $executed);
    }

    public function testMissingLaterFilePreventsAllExecution(): void
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['execute'])->getMock();
        $db->expects(self::never())->method('execute');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing or unreadable migration SQL file');
        $this->migration($db)->runFiles([$this->sqlFile('valid.sql', 'SELECT 1;'), $this->directory . '/missing.sql']);
    }

    public function testEmptyLaterFilePreventsAllExecution(): void
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['execute'])->getMock();
        $db->expects(self::never())->method('execute');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unreadable or empty migration SQL file');
        $this->migration($db)->runFiles([$this->sqlFile('valid.sql', 'SELECT 1;'), $this->sqlFile('empty.sql', " \n\t")]);
    }

    public function testFalseExecutionResultStopsTheBatch(): void
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['execute'])->getMock();
        $db->expects(self::exactly(2))->method('execute')->willReturn(true, false);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('second.sql');
        $this->migration($db)->runFiles([
            $this->sqlFile('first.sql', 'SELECT 1;'),
            $this->sqlFile('second.sql', 'SELECT 2;'),
            $this->sqlFile('third.sql', 'SELECT 3;'),
        ]);
    }

    public function testDatabaseExceptionIsPreservedAndStopsTheBatch(): void
    {
        $db = $this->getMockBuilder(Mysql::class)->disableOriginalConstructor()->onlyMethods(['execute'])->getMock();
        $failure = new \PDOException('Synthetic DDL failure');
        $db->expects(self::once())->method('execute')->willThrowException($failure);
        try {
            $this->migration($db)->runFiles([$this->sqlFile('first.sql', 'SELECT 1;'), $this->sqlFile('second.sql', 'SELECT 2;')]);
            self::fail('The driver exception was swallowed.');
        } catch (\PDOException $exception) {
            self::assertSame($failure, $exception);
        }
    }

    private function sqlFile(string $name, string $sql): string
    {
        $path = $this->directory . '/' . $name;
        file_put_contents($path, $sql);
        return $path;
    }

    private function migration(Mysql $db): SqlMigration
    {
        return new class ($db) extends SqlMigration {
            public function __construct(private readonly Mysql $db)
            {
            }

            public function getConnection(): AbstractAdapter
            {
                return $this->db;
            }

            public function runFile(string $path): void
            {
                $this->executeSqlFile($path);
            }

            public function runFiles(array $paths): void
            {
                $this->executeSqlFiles($paths);
            }
        };
    }
}
