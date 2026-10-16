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

namespace PhalconKit\Tests\Unit\Support;

use Phalcon\Db\RawValue;
use Phalcon\Di\Di as NativeDi;
use Phalcon\Mvc\Model\MetaData\Memory;
use Phalcon\Mvc\Model\Resultset\Complex;
use Phalcon\Support\Settings;
use PhalconKit\Config\Config;
use PhalconKit\Db\Adapter\Pdo\Mysql;
use PhalconKit\Di\Di;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Support\Fixtures\DatabaseTestCase;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon523ModelCompatibilityRecord;

/** Real Core SQL execution and complex hydration against an explicitly disposable server. */
final class Phalcon523ModelCompatibilityDatabaseTest extends DatabaseTestCase
{
    public function testRawValueSplicingAndNullRelationshipPersistenceUseCoreAdapter(): void
    {
        $this->withDatabase(function (Mysql $db, Manager $manager): void {
            $class = Phalcon523ModelCompatibilityRecord::class;
            $phql = 'SELECT * FROM [' . $class . '] WHERE id = :p: AND ownerId = :p1: AND label = :label:';
            foreach ([
                ['p' => 1, 'p1' => 2],
                ['p' => new RawValue('1'), 'p1' => 2],
                ['p' => new RawValue('1'), 'p1' => new RawValue('2')],
            ] as $bind) {
                $result = $manager->executeQuery($phql, $bind + ['label' => 'first']);
                self::assertCount(1, $result);
                self::assertSame(1, $result->getFirst()->id);
                self::assertSame(2, $result->getFirst()->ownerId);
            }
            $literal = $manager->executeQuery(
                'SELECT * FROM [' . $class . '] WHERE label = :literal: AND id = :literalId:',
                ['literal' => new RawValue("CONCAT('fir', 'st')"), 'literalId' => 1]
            );
            self::assertSame('first', $literal->getFirst()->label);

            $record = Phalcon523ModelCompatibilityRecord::findFirst(1);
            $owner = Phalcon523ModelCompatibilityRecord::findFirst(2);
            $record->setRelated('owner', $owner);
            self::assertSame($owner, $record->getRelated('owner'));
            $record->owner = null;
            self::assertSame(1, $record->ownerSetterCalls);
            self::assertNull($record->ownerId);
            self::assertTrue($record->hasDirtyRelatedAlias('owner'));
            self::assertNull($record->getRelated('owner'));
            self::assertTrue($record->save(), json_encode($record->getMessages()));
            self::assertNull(Phalcon523ModelCompatibilityRecord::findFirst(1)->ownerId);
            self::assertNull($db->fetchOne('SELECT owner_id FROM model_compat_records WHERE id = 1')['owner_id']);
            self::assertFalse($db->isUnderTransaction());
            self::assertFalse($record->hasDirtyRelated());
            self::assertNull($record->owner);
            self::assertNull($record->getRelated('owner'));
            self::assertNull($record->toArray()['owner']);
            self::assertFalse($record->isRelationshipLoaded('owner'));

            $record->OWNER = $owner;
            self::assertTrue($record->save());
            self::assertSame($owner, $record->owner);
            self::assertSame(2, $record->getRelated('owner')->id);
            self::assertSame(2, Phalcon523ModelCompatibilityRecord::findFirst(1)->ownerId);
            $owner->label = 'must not be saved implicitly';
            $record->label = 'changed';
            self::assertTrue($record->save());
            self::assertSame('second', Phalcon523ModelCompatibilityRecord::findFirst(2)->label);
            self::assertFalse($db->isUnderTransaction());

            $record->ownerId = 2;
            self::assertTrue($record->save());
            $record->otherOwner = null;
            self::assertTrue($record->hasDirtyRelatedAlias('otherOwner'));
            self::assertTrue($record->save());
            self::assertSame(2, Phalcon523ModelCompatibilityRecord::findFirst(1)->ownerId);
            self::assertFalse($record->hasDirtyRelated());
            self::assertFalse($db->isUnderTransaction());
        });
    }

    public function testRejectedNullAssignmentAndSaveLeavePersistedForeignKeyUnchanged(): void
    {
        $this->withDatabase(function (Mysql $db): void {
            $record = Phalcon523ModelCompatibilityRecord::findFirst(1);
            $record->rejectOwnerClear = true;
            try {
                $record->owner = null;
                self::fail('Application setter rejection must propagate.');
            } catch (\DomainException $exception) {
                self::assertSame('Owner change rejected.', $exception->getMessage());
            }
            self::assertSame(2, Phalcon523ModelCompatibilityRecord::findFirst(1)->ownerId);
            self::assertFalse($db->isUnderTransaction());

            $record = Phalcon523ModelCompatibilityRecord::findFirst(1);
            $record->owner = null;
            $record->rejectSave = true;
            self::assertFalse($record->save());
            self::assertSame(2, Phalcon523ModelCompatibilityRecord::findFirst(1)->ownerId);
            self::assertFalse($db->isUnderTransaction());
        });
    }

    public function testComplexHydrationUsesLateBindingPerRowAndHandlesUnmatchedModels(): void
    {
        $this->withDatabase(function (Mysql $db, Manager $manager): void {
            $previous = Settings::get('orm.late_state_binding');
            $previousEmpty = Settings::get('orm.resultset_empty_left_join_model');
            $class = Phalcon523ModelCompatibilityRecord::class;
            try {
                $query = 'SELECT copy.*, original.* FROM [' . $class . '] original'
                    . ' LEFT JOIN [' . $class . '] copy ON copy.id = original.id ORDER BY original.id';
                Settings::set('orm.late_state_binding', true);
                $rows = $manager->executeQuery($query);
                self::assertInstanceOf(Complex::class, $rows);
                $rows->rewind();
                $first = $rows->current();
                self::assertTrue($first->copy->lateStateBound);
                self::assertTrue($first->original->lateStateBound);
                self::assertSame('first', $first->original->label);
                Settings::set('orm.late_state_binding', false);
                $rows->next();
                $second = $rows->current();
                self::assertFalse($second->copy->lateStateBound);
                self::assertFalse($second->original->lateStateBound);
                self::assertSame('second', $second->original->label);

                Settings::set('orm.late_state_binding', true);
                Settings::set('orm.resultset_empty_left_join_model', false);
                $rows = $manager->executeQuery(str_replace('copy.id = original.id', 'copy.id = -1', $query));
                self::assertCount(2, $rows);
                foreach ($rows as $row) {
                    self::assertNull($row->copy);
                    self::assertTrue($row->original->lateStateBound);
                }
            } finally {
                Settings::set('orm.late_state_binding', $previous);
                Settings::set('orm.resultset_empty_left_join_model', $previousEmpty);
            }
        });
    }

    private function withDatabase(callable $test): void
    {
        $native = $this->connectTestDatabase();
        $descriptor = $native->getDescriptor();
        $native->close();
        $db = new Mysql($descriptor + ['dialectClass' => \PhalconKit\Db\Dialect\Mysql::class]);
        $database = 'phalconkit_model_compat_' . bin2hex(random_bytes(8));
        $previousDi = NativeDi::getDefault();
        $previousHelper = Helper::$helperFactory;
        $db->execute('CREATE DATABASE `' . $database . '`');
        try {
            $db->execute('USE `' . $database . '`');
            $db->execute('CREATE TABLE model_compat_records (id INT PRIMARY KEY, label VARCHAR(80) NULL, owner_id INT NULL, connection_service VARCHAR(80) NOT NULL DEFAULT \'\') ENGINE=InnoDB');
            $db->execute("INSERT INTO model_compat_records (id, label, owner_id) VALUES (1, 'first', 2), (2, 'second', NULL)");
            $di = new Di();
            NativeDi::setDefault($di);
            Helper::$helperFactory = null;
            $di->setShared('config', new Config());
            $di->setShared('helper', new HelperFactory());
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            $di->setShared('db', $db);
            $manager = new Manager();
            $di->setShared('modelsManager', $manager);
            $di->setShared('modelsMetadata', new Memory());
            $test($db, $di->getShared('modelsManager'));
        } finally {
            if ($db->isUnderTransaction()) {
                $db->rollback();
            }
            $db->execute('DROP DATABASE `' . $database . '`');
            $db->close();
            Helper::$helperFactory = $previousHelper;
            NativeDi::reset();
            if ($previousDi !== null) {
                NativeDi::setDefault($previousDi);
            }
        }
    }
}
