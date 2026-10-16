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

use Phalcon\Cache\Adapter\Memory as CacheMemory;
use Phalcon\Mvc\Model as NativeModel;
use Phalcon\Mvc\Model\MetaData;
use Phalcon\Mvc\Model\MetaData\Strategy\Annotations;
use Phalcon\Mvc\Model\MetaData\Stream;
use Phalcon\Storage\SerializerFactory;
use Phalcon\Support\Settings;
use PhalconKit\Db\Dialect\Mysql;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeMetaData;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon522MetadataModel;
use PhalconKit\Tests\Unit\Support\Fixtures\Phalcon523ModelCompatibilityRecord;

/** Native 5.22.1/5.23 behavior at Core's model, dialect, and metadata boundaries. */
final class Phalcon523ModelCompatibilityTest extends AbstractUnit
{
    public function testNullRelationInvokesApplicationSetterAndRetainsCoreDirtyTracking(): void
    {
        $model = new Phalcon523ModelCompatibilityRecord();
        $model->owner = null;

        self::assertSame(1, $model->ownerSetterCalls);
        self::assertNull($model->ownerId);
        self::assertTrue($model->hasDirtyRelatedAlias('OWNER'));
        self::assertNull($model->getDirtyRelatedAlias('owner'));
        self::assertNull($model->owner);
    }

    public function testRelationWritesKeepExistingNonNullAndNoSetterContracts(): void
    {
        $model = new Phalcon523ModelCompatibilityRecord();
        $owner = new Phalcon523ModelCompatibilityRecord();
        $model->owner = $owner;

        self::assertSame(0, $model->ownerSetterCalls);
        self::assertSame($owner, $model->owner);
        self::assertSame($owner, $model->getDirtyRelatedAlias('owner'));

        $model->otherOwner = null;
        self::assertTrue($model->hasDirtyRelatedAlias('otherOwner'));
        self::assertNull($model->otherOwner);
        self::assertSame(12, $model->ownerId);

        $model->requiredOwner = null;
        self::assertTrue($model->hasDirtyRelatedAlias('requiredOwner'));
        self::assertNull($model->requiredOwner);
        self::assertSame(12, $model->ownerId);
    }

    public function testExplicitNullWritesDispatchEachDynamicAliasSetterOnceAndRecoverAfterFailure(): void
    {
        $model = new Phalcon523ModelCompatibilityRecord();
        $model->__set('DYNAMICOWNER', null);
        self::assertSame(1, $model->dynamicOwnerSetterCalls);
        self::assertSame(1, $model->ownerSetterCalls);
        self::assertNull($model->dynamicOwner);
        self::assertFalse(property_exists($model, 'dynamicOwner'));

        $model->__set('dynamicOwner', null);
        self::assertSame(2, $model->dynamicOwnerSetterCalls);
        self::assertSame(2, $model->ownerSetterCalls);

        $model->rejectDynamicOwnerClear = true;
        try {
            $model->__set('dynamicOwner', null);
            self::fail('Application setter rejection must propagate.');
        } catch (\DomainException $exception) {
            self::assertSame('Dynamic owner change rejected.', $exception->getMessage());
        }
        $model->rejectDynamicOwnerClear = false;
        $model->__set('dynamicOwner', null);
        self::assertSame(4, $model->dynamicOwnerSetterCalls);
        self::assertSame(3, $model->ownerSetterCalls);

        $other = new Phalcon523ModelCompatibilityRecord();
        $other->dynamicOwner = null;
        self::assertSame(1, $other->dynamicOwnerSetterCalls);
        self::assertSame(1, $other->ownerSetterCalls);
    }

    public function testHydrationPreservesNullPolicyColumnMapsSettersAndReservedMethods(): void
    {
        $metadata = new FakeMetaData();
        $metadata->fakeColumnMap = ['id' => 'id', 'label' => 'label', 'connection_service' => 'connection_service'];
        $metadata->fakeReverseColumnMap = array_flip($metadata->fakeColumnMap);
        $this->di->setShared('modelsMetadata', $metadata);
        $previous = Settings::get('orm.call_setters_on_hydration');
        try {
            foreach ([false, true] as $setters) {
                Settings::set('orm.call_setters_on_hydration', $setters);
                foreach ([null, $metadata->fakeColumnMap] as $map) {
                    $base = new Phalcon523ModelCompatibilityRecord();
                    $model = NativeModel::cloneResultMap($base, ['label' => null, 'id' => null], $map);
                    self::assertSame(37, $model->id);
                    self::assertNull($model->label);
                    self::assertSame((int) $setters, $model->labelSetterCalls);
                    self::assertSame(NativeModel::DIRTY_STATE_PERSISTENT, $model->getDirtyState());

                    $model = NativeModel::cloneResultMap($base, ['id' => 9, 'label' => 'loaded', 'connection_service' => 'not-a-service'], $map);
                    self::assertSame(9, $model->id);
                    self::assertSame('loaded', $model->label);
                    self::assertSame((int) $setters, $model->labelSetterCalls);
                    self::assertSame('not-a-service', $model->connection_service);
                    self::assertSame('db', $model->getReadConnectionService());
                }
            }
        } finally {
            Settings::set('orm.call_setters_on_hydration', $previous);
        }
    }

    public function testCoreDialectKeepsIdentifierColumnAndCustomFunctionSql(): void
    {
        $dialect = new Mysql();
        $previous = Settings::get('db.escape_identifiers');
        try {
            Settings::set('db.escape_identifiers', true);
            foreach ([
                'catalog.records.id' => '`catalog`.`records`.`id`',
                'records.*' => '`records`.*',
                'rec`ords.id' => '`rec``ords`.`id`',
                'records..id' => '`records`..`id`',
            ] as $name => $sql) {
                self::assertSame($sql, $dialect->escape($name));
            }
            self::assertSame('"records"."id"', $dialect->escape('records.id', '"'));
            self::assertSame('`r`.`label` AS `title`', $dialect->getSqlColumn(['label', 'r', 'title']));
            self::assertSame('`label`', $dialect->getSqlColumn(['label', '']));
            self::assertSame('`r`.*', $dialect->getSqlColumn(['*', 'r']));
            self::assertSame('`r`.`label` AS `title`', $dialect->getSqlColumn([
                'type' => 'qualified', 'name' => 'label', 'domain' => 'r', 'sqlAlias' => 'title',
            ]));
            self::assertSame(' point(1, 2) AS `location`', $dialect->getSqlColumn([
                'type' => 'functionCall', 'name' => 'point', 'alias' => 'location',
                'arguments' => [['type' => 'literal', 'value' => '1'], ['type' => 'literal', 'value' => '2']],
            ]));
            Settings::set('db.escape_identifiers', false);
            self::assertSame('r.label AS title', $dialect->getSqlColumn(['label', 'r', 'title']));
        } finally {
            Settings::set('db.escape_identifiers', $previous);
        }
    }

    public function testInvalidCachedMetadataAndColumnMapsAreRebuilt(): void
    {
        $cache = new CacheMemory(new SerializerFactory());
        $metadata = new class ($cache) extends MetaData {
            public function __construct(CacheMemory $cache)
            {
                $this->adapter = $cache;
            }
        };
        $metadata->setDI($this->di);
        $metadata->setStrategy(new Annotations());
        $model = new Phalcon522MetadataModel(null, $this->di);
        // The public unique-key getters initialize metadata; seed before that first read.
        $metaKey = 'meta-' . strtolower($model::class);
        $mapKey = 'map-' . strtolower($model::class);
        $cache->set($metaKey, 1);
        $cache->set($mapKey, 'invalid');

        self::assertSame(['id'], $metadata->readMetaData($model)[MetaData::MODELS_PRIMARY_KEY]);
        self::assertIsArray($metadata->readColumnMap($model));
        self::assertIsArray($cache->get($metaKey));
        self::assertIsArray($cache->get($mapKey));
    }

    public function testStreamMetadataRecoversCorruptFilesAndReplacesCompleteValues(): void
    {
        $directory = sys_get_temp_dir() . '/phalconkit-model-metadata-' . bin2hex(random_bytes(8)) . '/';
        mkdir($directory, 0700);
        $metadata = new Stream(['metaDataDir' => $directory]);
        $metadata->setDI($this->di);
        $metadata->setStrategy(new Annotations());
        $model = new Phalcon522MetadataModel(null, $this->di);
        $metaKey = 'meta-' . strtolower($model::class);
        $mapKey = 'map-' . strtolower($model::class);
        try {
            foreach (['', '<?php return [', '<?php return 7;'] as $contents) {
                $metadata->reset();
                $metadata->write($metaKey, ['old']);
                $metadata->write($mapKey, ['old']);
                foreach (glob($directory . '*.php') as $file) {
                    file_put_contents($file, $contents);
                }
                self::assertNull($metadata->read($metaKey));
                self::assertNull($metadata->read($mapKey));
                self::assertSame(['id'], $metadata->readMetaData($model)[MetaData::MODELS_PRIMARY_KEY]);
                self::assertIsArray($metadata->readColumnMap($model));
                self::assertIsArray($metadata->read($metaKey));
                self::assertIsArray($metadata->read($mapKey));
                self::assertSame([], glob($directory . '*.tmp.*'));
            }
            $metadata->write('replacement', ['version' => 1]);
            $handle = fopen($directory . 'replacement.php', 'rb');
            try {
                $metadata->write('replacement', ['version' => 2]);
                self::assertStringContainsString("'version' => 1", stream_get_contents($handle));
                self::assertSame(['version' => 2], $metadata->read('replacement'));
                self::assertSame([], glob($directory . '*.tmp.*'));
            } finally {
                fclose($handle);
            }
        } finally {
            foreach (glob($directory . '*') as $file) {
                unlink($file);
            }
            rmdir($directory);
        }
    }
}
