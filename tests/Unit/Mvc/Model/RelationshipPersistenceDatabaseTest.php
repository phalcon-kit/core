<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\Di;
use Phalcon\Mvc\Model\MetaData\Memory;
use PhalconKit\Bootstrap\Config;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipGraphProject;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipGraphProjectTag;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipGraphTag;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipGraphTask;
use PhalconKit\Tests\Unit\Support\Fixtures\DatabaseTestCase;

/**
 * Opt-in regression for the documented many-to-many payloads and the
 * transaction around a nested save, against a disposable MySQL schema.
 */
final class RelationshipPersistenceDatabaseTest extends DatabaseTestCase
{
    private ?Mysql $connection = null;
    private ?\Phalcon\Di\DiInterface $previousDi = null;
    private ?HelperFactory $previousHelperFactory = null;
    private string $database = '';

    #[\Override]
    protected function setUp(): void
    {
        $this->connection = $this->connectTestDatabase();
        $this->previousDi = Di::getDefault();
        $this->previousHelperFactory = Helper::$helperFactory;
        $this->database = 'phalconkit_graph_' . bin2hex(random_bytes(8));
        $this->connection->execute('CREATE DATABASE `' . $this->database . '`');
        $this->connection->execute('USE `' . $this->database . '`');
        $this->connection->execute('CREATE TABLE graph_projects (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL) ENGINE=InnoDB');
        $this->connection->execute('CREATE TABLE graph_tasks (id INT AUTO_INCREMENT PRIMARY KEY, projectId INT NOT NULL, label VARCHAR(20) NOT NULL) ENGINE=InnoDB');
        $this->connection->execute('CREATE TABLE graph_tags (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(20) NOT NULL) ENGINE=InnoDB');
        $this->connection->execute('CREATE TABLE graph_project_tags (id INT AUTO_INCREMENT PRIMARY KEY, projectId INT NOT NULL, tagId INT NOT NULL, deleted TINYINT NOT NULL DEFAULT 0, UNIQUE KEY project_tag (projectId, tagId)) ENGINE=InnoDB');
        $this->connection->execute("INSERT INTO graph_projects VALUES (1, 'Garden')");
        $this->connection->execute("INSERT INTO graph_tags VALUES (1, 'outdoor'), (2, 'weekly'), (3, 'urgent')");
        $this->connection->execute('INSERT INTO graph_project_tags (projectId, tagId) VALUES (1, 1)');

        $di = new \PhalconKit\Di\Di();
        Di::setDefault($di);
        Helper::$helperFactory = null;
        $di->setShared('config', new Config());
        $di->setShared('helper', new HelperFactory());
        $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
        $di->setShared('db', $this->connection);
        $di->setShared('modelsManager', new \PhalconKit\Mvc\Model\Manager());
        $di->setShared('modelsMetadata', new Memory());
    }

    #[\Override]
    protected function tearDown(): void
    {
        if ($this->connection === null) {
            // Skipped before connecting: no disposable database configured.
            return;
        }
        if ($this->connection->isUnderTransaction()) {
            $this->connection->rollback();
        }
        $this->connection->execute('DROP DATABASE `' . $this->database . '`');
        $this->connection->close();
        Helper::$helperFactory = $this->previousHelperFactory;
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    /**
     * @return list<int>
     */
    private function linkedTagIds(): array
    {
        $rows = $this->connection->fetchAll('SELECT tagId FROM graph_project_tags WHERE projectId = 1 AND deleted = 0');
        $ids = array_map(static fn (array $row): int => (int)$row['tagId'], $rows);
        sort($ids);
        return $ids;
    }

    private function project(): RelationshipGraphProject
    {
        $project = RelationshipGraphProject::findFirst(1);
        self::assertInstanceOf(RelationshipGraphProject::class, $project);
        return $project;
    }

    public function testManyToManyIdsAddLinksAndKeepUnsubmittedOnesByDefault(): void
    {
        $project = $this->project();
        $project->assign(['tags' => [2, 3]]);
        self::assertTrue($project->save(), implode('; ', array_map('strval', $project->getMessages())));

        self::assertSame([1, 2, 3], $this->linkedTagIds());
        self::assertSame(3, RelationshipGraphTag::count(), 'Submitting existing tag IDs must not create tags.');

        // Submitting an already linked tag does not duplicate its link.
        $project = $this->project();
        $project->assign(['tags' => [3]]);
        self::assertTrue($project->save());
        self::assertSame([1, 2, 3], $this->linkedTagIds());
        self::assertFalse($this->connection->isUnderTransaction());
    }

    public function testManyToManyReplacementRemovesLinksButNotSharedTargets(): void
    {
        $project = $this->project();
        $project->setKeepMissingRelatedAlias('tags', false);
        $project->assign(['tags' => [3]]);
        self::assertTrue($project->save(), implode('; ', array_map('strval', $project->getMessages())));

        self::assertSame([3], $this->linkedTagIds());
        // A soft-delete link model marks the removed link deleted instead of
        // deleting the row; the shared tag row is never removed.
        self::assertSame(1, (int)$this->connection->fetchColumn('SELECT deleted FROM graph_project_tags WHERE projectId = 1 AND tagId = 1'));
        self::assertSame(3, RelationshipGraphTag::count(), 'Removing a link must keep the shared tag row.');

        // Submitting the tag again restores the soft-deleted link instead of
        // inserting a duplicate.
        $project = $this->project();
        $project->assign(['tags' => [1]]);
        self::assertTrue($project->save(), implode('; ', array_map('strval', $project->getMessages())));
        self::assertSame([1, 3], $this->linkedTagIds());
        self::assertSame(2, (int)$this->connection->fetchColumn('SELECT COUNT(*) FROM graph_project_tags WHERE projectId = 1'));
        self::assertSame('outdoor', RelationshipGraphTag::findFirst(1)->name);
    }

    public function testManyToManyFieldsUpdateTheSharedTarget(): void
    {
        $project = $this->project();
        $project->assign(['tags' => [['id' => 1, 'name' => 'garden']]]);
        self::assertTrue($project->save(), implode('; ', array_map('strval', $project->getMessages())));

        self::assertSame([1], $this->linkedTagIds());
        self::assertSame('garden', RelationshipGraphTag::findFirst(1)->name);

        // Allowing only the key links a tag without letting the caller edit it.
        $project = $this->project();
        $project->assign(['tags' => [['id' => 2, 'name' => 'renamed']]], ['tags' => ['id']]);
        self::assertTrue($project->save(), implode('; ', array_map('strval', $project->getMessages())));
        self::assertSame([1, 2], $this->linkedTagIds());
        self::assertSame('weekly', RelationshipGraphTag::findFirst(2)->name);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function databaseErrorPayloads(): iterable
    {
        yield 'has-many child' => [['tasks' => [['label' => 'Water plants'], ['label' => str_repeat('x', 40)]]]];
        yield 'many-to-many target' => [['tags' => [2, ['name' => str_repeat('x', 40)]]]];
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('databaseErrorPayloads')]
    public function testDatabaseErrorInANestedSaveRollsBackAndClosesTheTransaction(array $payload): void
    {
        $project = $this->project();
        $project->assign(['name' => 'Renamed garden'] + $payload);

        $saved = null;
        $error = null;
        try {
            $saved = $project->save();
        } catch (\Throwable $exception) {
            $error = $exception;
        }

        self::assertFalse($this->connection->isUnderTransaction(), 'The nested save left a transaction open' . ($error ? ' after ' . $error::class . ': ' . $error->getMessage() : '') . '.');
        self::assertNotTrue($saved);
        self::assertSame('Garden', $this->project()->name);
        self::assertSame(0, RelationshipGraphTask::count());
        self::assertSame([1], $this->linkedTagIds());
    }

    public function testDatabaseErrorKeepsTheCallersOwnTransactionOpen(): void
    {
        $this->connection->begin();
        $project = $this->project();
        $project->assign(['tags' => [['name' => str_repeat('x', 40)]]]);

        try {
            $project->save();
            self::fail('Expected the database to reject the oversized tag name.');
        } catch (\PDOException) {
        }

        self::assertSame(1, $this->connection->getTransactionLevel());
        $this->connection->rollback();
    }

    public function testFailedNestedChildRollsBackTheParentAndEarlierChildren(): void
    {
        $project = $this->project();
        $project->assign([
            'name' => 'Renamed garden',
            'tasks' => [
                ['label' => 'Water plants'],
                ['label' => null],
            ],
        ]);

        self::assertFalse($project->save());
        self::assertNotSame([], $project->getMessages());
        self::assertFalse($this->connection->isUnderTransaction());
        self::assertSame('Garden', $this->project()->name);
        self::assertSame(0, RelationshipGraphTask::count());
    }
}
