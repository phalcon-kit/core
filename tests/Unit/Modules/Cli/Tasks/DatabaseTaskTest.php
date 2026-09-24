<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhalconKit\Tests\Unit\Modules\Cli\Tasks;

use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\ResultInterface;
use PhalconKit\Bootstrap\Deployment;
use PhalconKit\Modules\Cli\Tasks\DatabaseTask;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Modules\Cli\Tasks\Fixtures\DatabaseTaskSeedDouble;

class DatabaseTaskTest extends AbstractUnit
{
    public function testUnconfiguredCommandsDoNotOpenADatabaseOrSeedAccounts(): void
    {
        $this->di->setShared('db', static function (): never {
            throw new \LogicException('Unconfigured maintenance must not open a database.');
        });
        $task = new DatabaseTask();
        $task->setDI($this->di);
        $task->initialize();

        $emptyInsert = ['saved' => 0, 'error' => [], 'message' => []];
        $this->assertSame([], $task->dropAction());
        $this->assertSame([], $task->truncateAction());
        $this->assertSame([], $task->fixEngineAction());
        $this->assertSame([], $task->optimizeAction());
        $this->assertSame([], $task->analyzeAction());
        $this->assertSame($emptyInsert, $task->insertAction());
        $this->assertSame(['engine' => [], 'optimize' => [], 'analyze' => []], $task->mainAction());
        $this->assertSame(['truncate' => [], 'insert' => $emptyInsert], $task->resetAction());
        $this->assertSame([
            'drop' => [], 'truncate' => [], 'engine' => [],
            'insert' => [], 'optimize' => [], 'analyze' => [],
        ], (new Deployment())->toArray());
    }

    public function testConfigReplacesSelectedInstructionsAndPreservesSubclassDefaults(): void
    {
        $this->getConfig()->merge(['deployment' => [
            'drop' => [],
            'truncate' => ['configured_table'],
            'engine' => ['configured_table' => 'InnoDB'],
        ]]);
        $task = new class extends DatabaseTask {
            public array $drop = ['subclass_obsolete'];
            public array $truncate = ['subclass_table'];
            public array $engine = ['subclass_table' => 'MyISAM'];

            public function initialize(): void
            {
                $this->analyze = ['subclass_table'];
                parent::initialize();
            }
        };
        $task->setDI($this->di);
        $task->initialize();

        $this->assertSame([], $task->drop);
        $this->assertSame(['configured_table'], $task->truncate);
        $this->assertSame(['configured_table' => 'InnoDB'], $task->engine);
        $this->assertSame(['subclass_table'], $task->analyze);
    }

    public function testConfiguredCommandsUseOnlyApplicationTablesAndSeedModels(): void
    {
        $this->getConfig()->merge(['deployment' => [
            'drop' => ['obsolete_app_table'],
            'truncate' => ['app_table'],
            'engine' => ['app_table' => 'InnoDB'],
            'optimize' => ['app_table'],
            'analyze' => ['app_table'],
            'insert' => [DatabaseTaskSeedDouble::class => [['label' => 'Application seed']]],
        ]]);
        $statements = [];
        $db = $this->createMock(AdapterInterface::class);
        $db->method('escapeIdentifier')->willReturnCallback(static fn (string $name): string => '`' . $name . '`');
        $db->expects($this->exactly(3))->method('execute')->willReturnCallback(
            static function (string $sql) use (&$statements): bool {
                $statements[] = $sql;
                return true;
            }
        );
        $result = $this->createStub(ResultInterface::class);
        $result->method('fetchAll')->willReturn([['Msg_text' => 'OK']]);
        $db->expects($this->exactly(2))->method('query')->willReturnCallback(
            static function (string $sql) use (&$statements, $result): ResultInterface {
                $statements[] = $sql;
                return $result;
            }
        );
        $this->di->setShared('db', $db);
        $task = new DatabaseTask();
        $task->setDI($this->di);
        $task->initialize();

        $this->assertSame(['obsolete_app_table' => true], $task->dropAction());
        $this->assertSame(['app_table' => true], $task->truncateAction());
        $this->assertSame(['app_table' => 'InnoDB'], $task->fixEngineAction());
        $this->assertSame(['app_table' => [['Msg_text' => 'OK']]], $task->optimizeAction());
        $this->assertSame(['app_table' => [['Msg_text' => 'OK']]], $task->analyzeAction());
        $this->assertSame([
            'DROP TABLE IF EXISTS `obsolete_app_table`',
            'TRUNCATE TABLE `app_table`',
            'ALTER TABLE `app_table` ENGINE = InnoDB',
            'OPTIMIZE TABLE `app_table`',
            'ANALYZE TABLE `app_table`',
        ], $statements);

        DatabaseTaskSeedDouble::$saved = [];
        try {
            $this->assertSame(['saved' => 1, 'error' => [], 'message' => []], $task->insertAction());
            $this->assertSame(['Application seed'], DatabaseTaskSeedDouble::$saved);
            $models = $this->getConfig()->pathToArray('permissions.roles.cli.models');
            $this->assertSame(['*'], $models[DatabaseTaskSeedDouble::class] ?? null);
        } finally {
            DatabaseTaskSeedDouble::$saved = [];
        }
    }
}
