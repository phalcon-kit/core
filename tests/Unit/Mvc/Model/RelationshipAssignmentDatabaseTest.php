<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Db\Adapter\Pdo\Mysql;
use Phalcon\Di\Di;
use Phalcon\Mvc\Model\MetaData\Memory;
use PhalconKit\Config\Config;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipSecurityChild;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipSecurityParent;
use PHPUnit\Framework\TestCase;

/** Opt-in regression exercising native PHQL, assignment and nested persistence. */
final class RelationshipAssignmentDatabaseTest extends TestCase
{
    public function testForeignChildCannotBeOverwrittenThroughRelationKeyFallback(): void
    {
        $socket = getenv('PHALCONKIT_RELATION_TEST_SOCKET');
        if (!$socket) {
            self::markTestSkipped('Set PHALCONKIT_RELATION_TEST_SOCKET to an isolated disposable database socket.');
        }
        $previousDi = Di::getDefault();
        $previousHelperFactory = Helper::$helperFactory;
        $database = 'phalconkit_relation_' . bin2hex(random_bytes(8));
        $connection = new Mysql(['unix_socket' => $socket, 'username' => 'root', 'password' => '']);
        $connection->execute('CREATE DATABASE `' . $database . '`');
        try {
            $connection->execute('USE `' . $database . '`');
            $connection->execute('CREATE TABLE security_parents (tenantId INT NOT NULL, id INT NOT NULL, name VARCHAR(255), PRIMARY KEY (tenantId, id)) ENGINE=InnoDB');
            $connection->execute('CREATE TABLE security_children (id INT AUTO_INCREMENT PRIMARY KEY, parentTenantId INT, parentId INT, name VARCHAR(255)) ENGINE=InnoDB');
            $connection->execute("INSERT INTO security_parents VALUES (7, 5, 'Owned parent'), (5, 7, 'Foreign parent')");
            $connection->execute("INSERT INTO security_children VALUES (11, 5, 7, 'Foreign child')");
            $di = new \PhalconKit\Di\Di();
            Di::setDefault($di);
            Helper::$helperFactory = null;
            $di->setShared('config', new Config());
            $di->setShared('helper', new HelperFactory());
            $di->setShared('filter', (new \Phalcon\Filter\FilterFactory())->newInstance());
            $di->setShared('db', $connection);
            $di->setShared('modelsManager', new Manager());
            $di->setShared('modelsMetadata', new Memory());

            $parent = RelationshipSecurityParent::findFirst(['conditions' => 'tenantId = 7 AND id = 5']);
            $parent->setRelationshipOptions(['enforceDirectOwnership' => true]);

            // Same named values, reversed request key order: formerly selected
            // foreign child 11, rewrote its owner, and passed the later save guard.
            $parent->assign(['child' => ['parentId' => 5, 'name' => 'New owned child', 'parentTenantId' => 7]]);
            self::assertTrue($parent->save());
            $foreign = RelationshipSecurityChild::findFirst(11);
            self::assertSame('Foreign child', $foreign->name);
            self::assertSame(5, (int)$foreign->parentTenantId);
            self::assertSame(7, (int)$foreign->parentId);
            $owned = RelationshipSecurityChild::findFirst(['conditions' => 'parentTenantId = 7 AND parentId = 5']);
            self::assertNotSame(11, (int)$owned->id);
            self::assertSame('New owned child', $owned->name);

            // A direct foreign relation-key lookup must fail before assignment,
            // even when the write allowlist would permit only its name.
            $parent = RelationshipSecurityParent::findFirst(['conditions' => 'tenantId = 7 AND id = 5']);
            $parent->setRelationshipOptions(['enforceDirectOwnership' => true]);
            try {
                $parent->assign(
                    ['child' => ['parentTenantId' => 5, 'parentId' => 7, 'name' => 'Rejected']],
                    ['child' => ['name']]
                );
                self::fail('Expected the foreign relationship-key lookup to be rejected.');
            } catch (InvalidArgumentException $exception) {
                self::assertSame(400, $exception->getCode());
            }
            self::assertFalse($parent->hasDirtyRelated());
            self::assertSame('Foreign child', RelationshipSecurityChild::findFirst(11)->name);

            // A legitimate sparse update still resolves and saves the owned child.
            $parent->assign(['child' => ['name' => 'Updated owned child']], ['child' => ['name']]);
            self::assertTrue($parent->save());
            self::assertSame('Updated owned child', RelationshipSecurityChild::findFirst($owned->id)->name);
            self::assertSame('Foreign child', RelationshipSecurityChild::findFirst(11)->name);

            // Composite primary-key lookup must also preserve column/value
            // association for a belongs-to save with a restricted field list.
            $owned = RelationshipSecurityChild::findFirst($owned->id);
            $owned->assign(
                ['owner' => ['id' => 5, 'name' => 'Updated owned parent', 'tenantId' => 7]],
                ['owner' => ['name']]
            );
            self::assertTrue($owned->save());
            self::assertSame('Updated owned parent', RelationshipSecurityParent::findFirst(['conditions' => 'tenantId = 7 AND id = 5'])->name);
            self::assertSame('Foreign parent', RelationshipSecurityParent::findFirst(['conditions' => 'tenantId = 5 AND id = 7'])->name);
            // Native findFirst eager loading must populate Core's read-only
            // relationship cache without turning loaded relations into writes.
            $eager = RelationshipSecurityParent::findFirst([
                'conditions' => 'tenantId = 7 AND id = 5',
                'eager' => ['children'],
            ]);
            self::assertTrue($eager->isRelationshipLoaded('children'));
            self::assertTrue($eager->hasLoadedRelatedAlias('children'));
            self::assertFalse($eager->hasDirtyRelated());
            self::assertCount(1, $eager->getRelated('children'));
            self::assertSame('Updated owned child', $eager->toArray()['children'][0]['name']);
            self::assertNull(RelationshipSecurityParent::findFirst([
                'conditions' => 'tenantId = -1',
                'eager' => ['children'],
            ]));

            $empty = RelationshipSecurityParent::find(['conditions' => 'tenantId = -1']);
            self::assertNull($empty->current());
            self::assertNull($empty->current());

            $manager = $di->getShared('modelsManager');
            $complex = $manager->executeQuery(
                'SELECT p.*, c.* FROM [' . RelationshipSecurityParent::class . '] p'
                . ' JOIN [' . RelationshipSecurityChild::class . '] c'
                . ' ON p.tenantId = c.parentTenantId AND p.id = c.parentId'
            );
            self::assertInstanceOf(\Phalcon\Mvc\Model\Resultset\Complex::class, $complex);
            $cached = unserialize(serialize($complex));
            self::assertInstanceOf(\Phalcon\Mvc\Model\Resultset\Complex::class, $cached);
            self::assertTrue($cached->valid());
            self::assertInstanceOf(\Phalcon\Mvc\Model\Row::class, $cached->current());

            $literal = $manager->executeQuery(
                'SELECT "line\\nbreak" AS value FROM [' . RelationshipSecurityParent::class . '] LIMIT 1'
            );
            self::assertSame("line\nbreak", $literal->getFirst()->value);

            self::assertFalse($connection->isUnderTransaction());
        } finally {
            if ($connection->isUnderTransaction()) {
                $connection->rollback();
            }
            $connection->execute('DROP DATABASE `' . $database . '`');
            $connection->close();
            Helper::$helperFactory = $previousHelperFactory;
            Di::reset();
            if ($previousDi !== null) {
                Di::setDefault($previousDi);
            }
        }
    }
}
