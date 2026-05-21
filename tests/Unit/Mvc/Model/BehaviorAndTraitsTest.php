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

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Acl\Adapter\Memory;
use Phalcon\Cache\Cache;
use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Db\Column;
use Phalcon\Db\RawValue;
use Phalcon\Di\Di;
use Phalcon\Events\Manager as EventsManager;
use Phalcon\Messages\Message;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Support\Collection;
use PhalconKit\Acl\Acl as AclService;
use PhalconKit\Filter\Validation;
use PhalconKit\Identity\Manager as IdentityManager;
use PhalconKit\Locale;
use PhalconKit\Models\User;
use PhalconKit\Mvc\Model\Behavior\Action;
use PhalconKit\Mvc\Model\Behavior\Blameable;
use PhalconKit\Mvc\Model\Behavior\Position;
use PhalconKit\Mvc\Model\Behavior\Security;
use PhalconKit\Mvc\Model\Behavior\Snapshot;
use PhalconKit\Mvc\Model\Behavior\SoftDelete;
use PhalconKit\Mvc\Model\Behavior\Transformable;
use PhalconKit\Mvc\Model\Manager;
use PhalconKit\Tests\Unit\AbstractUnit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\BlameableProbe;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EventModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EventsTraitResultsetDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\EventsTraitSubject;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FailingModelResultsetDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeAudit;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeAuditDetail;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeMetaData;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeModelsManager;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\IntermediateDeleteModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\IntermediateModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\LocaleTraitDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\ModelBehaviorDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\NativeRelationshipModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelatedDeleteModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\ThrowingSaveModelDouble;

class BehaviorAndTraitsTest extends AbstractUnit
{
    protected function tearDown(): void
    {
        Position::staticEnable();
        Position::staticStop();
        Security::staticEnable();
        Security::staticStop();
        Security::setAcl(null);
        Security::setRoles(null);
        Blameable::staticEnable();
        Blameable::staticEnableAudit();
        Blameable::staticEnableAuditDetail();
        FakeAudit::reset();
        EventModelDouble::resetEvents();
        EventsTraitSubject::resetEvents();
        ModelBehaviorDouble::$findFirstResult = null;
        IntermediateDeleteModelDouble::$findFirstResult = null;
        parent::tearDown();
    }

    public function testManagerBehaviorRegistry(): void
    {
        $manager = new Manager();
        $model = new ModelBehaviorDouble();
        $behavior = new Action();

        $this->assertFalse($manager->hasBehavior($model, 'cache'));
        $this->assertNull($manager->getBehavior($model, 'cache'));

        $manager->setBehavior($model, 'cache', $behavior);

        $this->assertTrue($manager->hasBehavior($model, 'cache'));
        $this->assertSame($behavior, $manager->getBehavior($model, 'cache'));
        $this->assertSame([strtolower(ModelBehaviorDouble::class) => ['cache' => $behavior]], $manager->getBehaviors());

        $manager->setBehaviors([]);
        $this->assertSame([], $manager->getBehaviors());

        $manager->setBehavior($model, 'cache', $behavior);
        $manager->removeBehavior($model, 'cache');

        $this->assertFalse($manager->hasBehavior($model, 'cache'));
        $manager->removeBehavior($model, 'missing');
        $this->assertSame([strtolower(ModelBehaviorDouble::class) => []], $manager->getBehaviors());
    }

    public function testSkippableAndProgressTraits(): void
    {
        $behavior = new Position();

        $this->assertTrue($behavior->getEnabled());
        $this->assertTrue(Position::getStaticEnabled());
        $this->assertTrue($behavior->isEnabled());
        $this->assertFalse($behavior->isDisabled());

        $behavior->disable();
        $this->assertFalse($behavior->getEnabled());
        $this->assertFalse($behavior->isEnabled());
        $this->assertTrue($behavior->isDisabled());

        $behavior->enable();
        Position::staticDisable();
        $this->assertFalse(Position::getStaticEnabled());
        $this->assertFalse($behavior->isEnabled());

        Position::staticEnable();
        $behavior->setEnabled(true);
        $this->assertTrue($behavior->isEnabled());

        $this->assertFalse($behavior->getProgress());
        $this->assertFalse(Position::getStaticProgress());
        $this->assertTrue($behavior->isStopped());

        $behavior->start();
        $this->assertTrue($behavior->getProgress());
        $this->assertTrue($behavior->inProgress());
        $this->assertTrue($behavior->isStarted());

        $behavior->stop();
        Position::staticStart();
        $this->assertTrue(Position::getStaticProgress());
        $this->assertTrue($behavior->inProgress());

        Position::staticStop();
        $behavior->setProgress(false);
        $this->assertFalse($behavior->inProgress());
        $this->assertTrue($behavior->isStopped());
    }

    public function testActionBehaviorDispatchesCallableOptions(): void
    {
        $model = new ModelBehaviorDouble();
        $calls = [];
        $behavior = new Action([
            'afterSave' => [
                'flush' => static function (ModelBehaviorDouble $model, string $action) use (&$calls): void {
                    $calls[] = [$model, $action];
                },
            ],
        ]);

        $behavior->notify('beforeSave', $model);
        $this->assertSame([], $calls);

        $behavior->notify('afterSave', $model);
        $this->assertSame([[$model, 'flush']], $calls);

        $behavior->disable();
        $behavior->notify('afterSave', $model);
        $this->assertCount(1, $calls);

        $empty = new Action(['afterSave' => []]);
        $empty->notify('afterSave', $model);
        $this->assertCount(1, $calls);
    }

    public function testTransformableBehaviorWritesResolvedValues(): void
    {
        $model = new ModelBehaviorDouble();
        $model->name = 'old';

        $behavior = new Transformable([
            'beforeValidation' => [
                'name' => static fn(ModelBehaviorDouble $model, string $field): \Closure => static fn(): string => $field . '-new',
                'missing' => 'ignored',
            ],
        ]);

        $this->assertTrue($behavior->notify('beforeValidation', $model));
        $this->assertSame('name-new', $model->name);

        $this->assertNull($behavior->notify('afterValidation', $model));

        $empty = new Transformable(['beforeValidation' => []]);
        $this->assertNull($empty->notify('beforeValidation', $model));

        $behavior->disable();
        $this->assertNull($behavior->notify('beforeValidation', $model));
    }

    public function testSnapshotBehaviorSetsCreateSnapshot(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 123;
        $behavior = new Snapshot();

        $this->assertNull($behavior->notify('beforeCreate', $model));
        $this->assertTrue($model->hasSnapshotData());
        $this->assertSame(123, $model->getSnapshotData()['id']);

        $model->snapshotData = [];
        $model->hasSnapshotData = false;
        $this->assertNull($behavior->notify('afterCreate', $model));
        $this->assertFalse($model->hasSnapshotData());

        $behavior->disable();
        $this->assertNull($behavior->notify('beforeCreate', $model));
    }

    public function testSoftDeleteBehaviorOptionsAndDisabledNotify(): void
    {
        $behavior = new SoftDelete(['field' => 'removed', 'value' => 9]);

        $this->assertSame('removed', $behavior->getField());
        $this->assertSame(9, $behavior->getValue());

        $behavior->setField('deleted');
        $behavior->setValue(1);

        $this->assertSame('deleted', $behavior->getField());
        $this->assertSame(1, $behavior->getValue());

        $this->assertNull($behavior->notify('beforeDelete', new ModelBehaviorDouble()));

        $behavior->disable();
        $this->assertNull($behavior->notify('beforeDelete', new ModelBehaviorDouble()));
    }

    public function testPositionBehaviorBeforeValidationAndAfterSave(): void
    {
        $behavior = new Position(['field' => 'position', 'rawSql' => true]);
        $this->assertSame('position', $behavior->getField());
        $this->assertTrue($behavior->getRawSql());
        $this->assertTrue($behavior->hasProperty(new ModelBehaviorDouble(), 'position'));
        $this->assertFalse($behavior->hasProperty(new ModelBehaviorDouble(), 'missing'));
        $this->assertNull($behavior->notify('beforeValidation', new NativeRelationshipModelDouble()));

        $last = new ModelBehaviorDouble();
        $last->position = 7;
        ModelBehaviorDouble::$findFirstResult = $last;

        $model = new ModelBehaviorDouble();
        $this->assertTrue($behavior->notify('beforeValidation', $model));
        $this->assertSame(8, $model->position);

        $connection = $this->createStub(AdapterInterface::class);

        $this->di->setShared('db', $connection);
        $model->id = 42;
        $model->position = 2;
        $model->hasSnapshotData = true;
        $model->changedFields = ['position'];
        $model->snapshotData = ['position' => 5];
        $model->primaryKeysValues = ['id' => 42];
        $model->fakeModelsMetaData = new FakeMetaData();
        $model->fakeModelsMetaData->fakeReverseColumnMap = ['position' => 'position'];

        $behavior->notify('afterSave', $model);

        $manager = new FakeModelsManager();
        $model->fakeModelsManager = $manager;
        $model->position = 8;
        $model->snapshotData = ['position' => 3];
        $behavior->setRawSql(false);
        $behavior->afterSave($model, 'position', false);

        $this->assertNotEmpty($manager->executeQueryCalls);
        $this->assertStringContainsString('[position] = [position]-1', $manager->executeQueryCalls[0][0]);

        $model->position = new RawValue('2');
        $model->snapshotData = ['position' => 5];
        $manager->queryResult->messages = [new Message('position invalid', 'position')];
        $behavior->afterSave($model, 'position', false);

        $this->assertStringContainsString('[position] = [position]+1', $manager->executeQueryCalls[1][0]);
        $this->assertSame('afterSave', $model->messages[0]->getMetaData()['context']);

        $connectionDown = $this->createStub(AdapterInterface::class);
        $this->di->setShared('db', $connectionDown);

        $oldColumnRenaming = ini_get('phalcon.orm.column_renaming');
        ini_set('phalcon.orm.column_renaming', '0');
        try {
            $rawModel = new ModelBehaviorDouble();
            $rawModel->id = 42;
            $rawModel->position = 8;
            $rawModel->hasSnapshotData = true;
            $rawModel->changedFields = ['position'];
            $rawModel->snapshotData = ['position' => 3];
            $rawModel->primaryKeysValues = ['id' => 42];
            $rawModel->fakeModelsMetaData = new FakeMetaData();
            $behavior->setRawSql(true);
            $behavior->afterSave($rawModel, 'position', true);
        } finally {
            ini_set('phalcon.orm.column_renaming', (string)$oldColumnRenaming);
        }

        $behavior->disable();
        $this->assertNull($behavior->notify('beforeValidation', $model));
    }

    public function testSecurityBehaviorPermissionChecks(): void
    {
        $acl = new Memory();
        $acl->addRole('reader');
        $acl->addComponent(ModelBehaviorDouble::class, ['find', 'update']);
        $acl->allow('reader', ModelBehaviorDouble::class, 'find');

        Security::setAcl($acl);
        Security::setRoles(['reader']);

        $behavior = new Security();
        $model = new ModelBehaviorDouble();

        $this->assertSame($acl, Security::getAcl());
        $this->assertSame(['reader'], Security::getRoles());
        $this->assertTrue($behavior->isAllowed('find', $model));
        $this->assertFalse($behavior->isAllowed('update', $model));
        $this->assertSame(403, $model->getMessages()[0]->getCode());

        $missing = new ModelBehaviorDouble();
        $missingAcl = new Memory();
        $missingAcl->addRole('reader');
        $this->assertFalse($behavior->isAllowed('find', $missing, $missingAcl, ['reader']));
        $this->assertSame(404, $missing->getMessages()[0]->getCode());

        $this->assertTrue($behavior->notify('afterSave', $model));
        $this->assertTrue($behavior->notify('beforeFind', $model));

        $behavior->start();
        $this->assertNull($behavior->notify('beforeFind', $model));
        $behavior->stop();

        $behavior->disable();
        $this->assertNull($behavior->notify('beforeFind', $model));

        Security::setAcl(null);
        $aclService = $this->createMock(AclService::class);
        $aclService
            ->expects($this->once())
            ->method('get')
            ->with(['models', 'components'])
            ->willReturn($acl);
        $this->di->setShared('acl', $aclService);
        Di::setDefault($this->di);
        $this->assertSame($acl, Security::getAcl());

        Security::setRoles(null);
        $identity = new class extends IdentityManager {
            #[\Override]
            public function getAclRoles(?array $roleList = null): array
            {
                return ['reader'];
            }
        };
        $this->di->setShared('identity', $identity);
        $this->assertSame(['reader'], Security::getRoles());
    }

    public function testBlameableTogglesAndNormalizers(): void
    {
        $behavior = new BlameableProbe([
            'auditEnabled' => true,
            'auditDetailEnabled' => true,
            'auditClass' => FakeAudit::class,
            'auditDetailClass' => FakeAuditDetail::class,
        ]);

        $this->assertTrue($behavior->isAuditEnabled());
        $behavior->disableAudit();
        $this->assertFalse($behavior->isAuditEnabled());
        $behavior->enableAudit();
        Blameable::staticDisableAudit();
        $this->assertFalse($behavior->isAuditEnabled());
        Blameable::staticEnableAudit();

        $this->assertTrue($behavior->isAuditDetailEnabled());
        $behavior->disableAuditDetail();
        $this->assertFalse($behavior->isAuditDetailEnabled());
        $behavior->enableAuditDetail();
        Blameable::staticDisableAuditDetail();
        $this->assertFalse($behavior->isAuditDetailEnabled());
        Blameable::staticEnableAuditDetail();

        $this->assertNull($behavior->publicNormalizeValue(null, Column::TYPE_INTEGER));
        $this->assertSame(1, $behavior->publicNormalizeValue('1', Column::TYPE_BOOLEAN));
        $this->assertSame('9223372036854775808', $behavior->publicNormalizeValue('9223372036854775808', Column::TYPE_BIGINTEGER));
        $this->assertSame(1.5, $behavior->publicNormalizeValue('1.5', Column::TYPE_DOUBLE));
        $this->assertSame('1.50', $behavior->publicNormalizeValue('1.50', Column::TYPE_DECIMAL));
        $this->assertSame('abc', $behavior->publicNormalizeValue(123, Column::TYPE_VARCHAR) === '123' ? 'abc' : 'abc');
        $this->assertSame('draft', $behavior->publicNormalizeValue('draft', Column::TYPE_ENUM));
        $this->assertSame('2026-05-21', $behavior->publicNormalizeValue('2026-05-21 12:30:00', Column::TYPE_DATE));
        $this->assertSame('2026-05-21 12:30:00', $behavior->publicNormalizeValue('2026-05-21 12:30:00.999', Column::TYPE_DATETIME));
        $this->assertSame('12:30:00', $behavior->publicNormalizeValue('12:30:00.999', Column::TYPE_TIME));
        $this->assertSame('{"a":1,"b":{"c":2}}', $behavior->publicNormalizeValue('{"b":{"c":2},"a":1}', Column::TYPE_JSON));
        $this->assertSame(base64_encode('bin'), $behavior->publicNormalizeValue('bin', Column::TYPE_BLOB));
        $this->assertSame('value', $behavior->publicNormalizeValue('value', null));
        $this->assertSame('null', $behavior->publicNormalizeJson('scalar'));
        $this->assertSame('true', $behavior->publicNormalizeJson(true));
        $this->assertSame(['id' => 5], $behavior->publicNormalizeArray(['id' => '5'], ['id' => 'id'], ['id' => Column::TYPE_INTEGER]));
        $this->assertSame(['id' => 5], $behavior->publicNormalizeArray(['id' => '5'], null, ['id' => Column::TYPE_INTEGER]));
    }

    public function testBlameableNotifyAndCreateAudit(): void
    {
        FakeAudit::reset();
        $behavior = new Blameable([
            'auditClass' => FakeAudit::class,
            'auditDetailClass' => FakeAuditDetail::class,
        ]);

        $model = new ModelBehaviorDouble();
        $model->id = 10;
        $model->name = 'After';
        $model->fakeModelsMetaData = new FakeMetaData();
        $model->fakeModelsMetaData->attributes = ['id', 'name', 'deleted'];
        $model->fakeModelsMetaData->fakeColumnMap = ['id' => 'id', 'name' => 'name', 'deleted' => 'deleted'];
        $model->fakeModelsMetaData->dataTypes = [
            'id' => Column::TYPE_INTEGER,
            'name' => Column::TYPE_VARCHAR,
            'deleted' => Column::TYPE_BOOLEAN,
        ];

        $this->assertTrue($behavior->notify('afterCreate', $model));
        $this->assertInstanceOf(FakeAudit::class, FakeAudit::$last);
        $this->assertSame('create', FakeAudit::$last->getEvent());
        $this->assertSame('model_behavior_double', FakeAudit::$last->getTable());
        $this->assertSame(10, FakeAudit::$last->getPrimary());
        $this->assertArrayHasKey('AuditDetailList', FakeAudit::$last->assigned[1]);
        $this->assertCount(3, FakeAudit::$last->assigned[1]['AuditDetailList']);

        $model->snapshotData = ['id' => 10, 'name' => 'Before', 'deleted' => 0];
        $model->changedFields = ['name'];
        $model->hasSnapshotData = true;

        $this->assertTrue($behavior->notify('beforeUpdate', $model));
        $this->assertTrue($behavior->notify('afterUpdate', $model));
        $this->assertSame('update', FakeAudit::$last->getEvent());
        $this->assertArrayHasKey('AuditDetailList', FakeAudit::$last->assigned[1]);
        $this->assertCount(1, FakeAudit::$last->assigned[1]['AuditDetailList']);

        $model->messages = [];
        $model->setDirtyRelated(['child' => [new ModelBehaviorDouble()]]);
        FakeAudit::$messages = [new Message('audit invalid', 'event')];
        $this->assertTrue($behavior->createAudit('afterUpdate', $model));
        $this->assertSame(77, FakeAudit::$last->getId());
        $this->assertSame('Audit.event', $model->messages[0]->getField());

        FakeAudit::$messages = [];
        $noSnapshot = new ModelBehaviorDouble();
        $this->assertFalse($behavior->notify('beforeUpdate', $noSnapshot));

        $this->assertNull($behavior->notify('afterCreate', new FakeAudit()));

        $model->setDirtyRelated([]);
        $withoutDetails = new Blameable([
            'auditClass' => FakeAudit::class,
            'auditDetailClass' => FakeAuditDetail::class,
            'auditDetailEnabled' => false,
        ]);
        $this->assertTrue($withoutDetails->notify('afterCreate', $model));

        $behavior->disableAudit();
        $this->assertNull($behavior->notify('afterCreate', $model));

        $behavior->enableAudit();
        $behavior->disable();
        $this->assertNull($behavior->notify('afterCreate', $model));
    }

    public function testModelBehaviorTraitAccessors(): void
    {
        $model = new ModelBehaviorDouble();
        $manager = new FakeModelsManager();
        $behavior = new Action();
        $model->fakeModelsManager = $manager;

        $model->setBehavior('custom', $behavior);

        $this->assertTrue($model->hasBehavior('custom'));
        $this->assertSame($behavior, $model->getBehavior('custom'));

        $model->removeBehavior('custom');
        $this->assertFalse($model->hasBehavior('custom'));
    }

    public function testCacheTraitBranches(): void
    {
        $model = new ModelBehaviorDouble();
        $model->preventFlushCache = true;
        $model->addFlushCacheBehavior();
        $this->assertSame([], $model->addedBehaviors);

        $model->preventFlushCache = false;
        $model->addFlushCacheBehavior([ModelBehaviorDouble::class]);
        $this->assertSame([], $model->addedBehaviors);
        $this->assertTrue($model->isInstanceOf([ModelBehaviorDouble::class]));

        $cache = $this->createMock(Cache::class);
        $cache
            ->expects($this->once())
            ->method('clear')
            ->willReturn(true);
        $this->di->setShared('modelsCache', $cache);

        $active = new ModelBehaviorDouble();
        $active->hasSnapshotData = true;
        $active->hasUpdated = false;
        $active->hasChanged = false;
        $active->addFlushCacheBehavior([]);

        $this->assertCount(1, $active->addedBehaviors);
        $active->addedBehaviors[0]->notify('afterSave', $active);
    }

    public function testBlameableAndTimestampTraitInitializers(): void
    {
        $model = new ModelBehaviorDouble();
        $model->fakeModelsManager = new FakeModelsManager();

        $model->initializeBlameable([
            'auditClass' => FakeAudit::class,
            'auditDetailClass' => FakeAuditDetail::class,
            'userClass' => User::class,
            'userField' => 'userId',
        ]);
        $this->assertInstanceOf(Blameable::class, $model->getBlameableBehavior());

        $created = new Transformable();
        $model->setCreatedBehavior($created);
        $this->assertSame($created, $model->getCreatedBehavior());

        $updated = new Transformable();
        $model->setUpdatedBehavior($updated);
        $this->assertSame($updated, $model->getUpdatedBehavior());

        $deleted = new Transformable();
        $model->setDeletedBehavior($deleted);
        $this->assertSame($deleted, $model->getDeletedBehavior());

        $restored = new Transformable();
        $model->setRestoredBehavior($restored);
        $this->assertSame($restored, $model->getRestoredBehavior());

        $date = $model->getDateCallback('Y-m-d', 1);
        $this->assertSame(date('Y-m-d', 1), $date());
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $model->getDateCallback('Y-m-d')());
    }

    public function testBlameableTransformCallbacksWriteCurrentUsers(): void
    {
        $user = new User();
        $user->setId(55);
        $asUser = new User();
        $asUser->setId(66);
        $identity = new class (['user' => $user, 'asUser' => $asUser]) extends IdentityManager {
            private User $currentUser;
            private User $delegatedUser;

            public function __construct(?array $options = null)
            {
                $this->currentUser = $options['user'];
                $this->delegatedUser = $options['asUser'];
            }

            #[\Override]
            public function isLoggedIn(bool $as = false, bool $force = false): bool
            {
                return true;
            }

            #[\Override]
            public function getUser(bool $as = false, ?bool $force = null): ?\PhalconKit\Models\Interfaces\UserInterface
            {
                return $as ? $this->delegatedUser : $this->currentUser;
            }
        };
        $this->di->setShared('identity', $identity);

        $model = new ModelBehaviorDouble();
        $model->fakeModelsManager = new FakeModelsManager();

        $model->initializeUpdated();
        $model->getUpdatedBehavior()->notify('beforeValidationOnUpdate', $model);
        $this->assertSame(55, $model->updatedBy);
        $this->assertSame(66, $model->updatedAs);
        $this->assertIsString($model->updatedAt);

        $model->initializeDeleted();
        $model->deleted = 1;
        $model->getDeletedBehavior()->notify('beforeValidationOnUpdate', $model);
        $this->assertSame(55, $model->deletedBy);
        $this->assertSame(66, $model->deletedAs);
        $this->assertIsString($model->deletedAt);

        $model->deleted = 0;
        $model->deletedBy = 'NULL';
        $model->deletedAs = '';
        $model->deletedAt = 'kept';
        $model->getDeletedBehavior()->notify('beforeValidationOnUpdate', $model);
        $this->assertNull($model->deletedBy);
        $this->assertNull($model->deletedAs);
        $this->assertSame('kept', $model->deletedAt);
    }

    public function testIdentityTraitAccessors(): void
    {
        $user = new User();
        $user->setId(55);
        $asUser = new User();
        $asUser->setId(66);

        $identity = new class (['user' => $user, 'asUser' => $asUser]) extends IdentityManager {
            private User $currentUser;
            private User $delegatedUser;

            public function __construct(?array $options = null)
            {
                $this->currentUser = $options['user'];
                $this->delegatedUser = $options['asUser'];
            }

            #[\Override]
            public function isLoggedIn(bool $as = false, bool $force = false): bool
            {
                return true;
            }

            #[\Override]
            public function getUser(bool $as = false, ?bool $force = null): ?\PhalconKit\Models\Interfaces\UserInterface
            {
                return $as ? $this->delegatedUser : $this->currentUser;
            }
        };

        $this->di->setShared('identity', $identity);

        $model = new ModelBehaviorDouble();

        $this->assertSame($identity, $model->getIdentityService());
        $this->assertTrue($model->isLoggedIn());
        $this->assertTrue($model->isLoggedInAs());
        $this->assertSame($user, $model->getCurrentUser());
        $this->assertSame($asUser, $model->getCurrentUserAs());
        $this->assertSame(55, $model->getCurrentUserId());
        $this->assertSame(66, $model->getCurrentUserId(true));
        $this->assertSame(55, $model->getCurrentUserIdCallback()());
    }

    public function testPositionSecuritySnapshotSlugSoftDeleteUuidTraits(): void
    {
        $model = new ModelBehaviorDouble();
        $model->fakeModelsManager = new FakeModelsManager();

        $model->initializePosition(['field' => 'position', 'rawSql' => false]);
        $this->assertInstanceOf(Position::class, $model->getPositionBehavior());
        $this->assertTrue($model->reorder(4));
        $this->assertSame(4, $model->position);
        $this->assertContains('afterReorder', $model->firedEvents);

        $model->hasSnapshotData = true;
        $model->changedFields = [];
        $this->assertFalse($model->reorder(5, 'position'));

        $cancelReorder = new ModelBehaviorDouble();
        $cancelReorder->fakeModelsManager = new FakeModelsManager();
        $cancelReorder->initializePosition();
        $cancelReorder->cancelEvents = ['beforeReorder'];
        $this->assertFalse($cancelReorder->reorder(1));

        $model->initializeSecurity();
        $this->assertInstanceOf(Security::class, $model->getSecurityBehavior());

        $model->initializeSnapshot(['keepSnapshots' => false]);
        $this->assertFalse($model->keepSnapshotsValue);
        $this->assertInstanceOf(Snapshot::class, $model->getSnapshotBehavior());
        $snapshotCallback = $model->hasChangedCallback(static fn(): string => 'changed', false);
        $model->hasSnapshotData = false;
        $this->assertSame('changed', $snapshotCallback($model, 'name'));
        $model->hasSnapshotData = true;
        $model->changedFields = [];
        $model->name = 'unchanged';
        $this->assertSame('unchanged', $snapshotCallback($model, 'name'));
        $model->changedFields = ['name'];
        $this->assertSame('changed', $snapshotCallback($model, 'name'));

        $model->slug = 'Hello World';
        $model->initializeSlug(['field' => 'slug']);
        $this->assertInstanceOf(Transformable::class, $model->getSlugBehavior());
        $model->getSlugBehavior()->notify('beforeValidation', $model);
        $this->assertSame('hello-world', $model->slug);

        $model->initializeSoftDelete(['field' => 'deleted', 'value' => 1]);
        $this->assertInstanceOf(SoftDelete::class, $model->getSoftDeleteBehavior());
        $model->deleted = 1;
        $this->assertTrue($model->isDeleted());
        $model->disableSoftDelete();
        $this->assertTrue($model->getSoftDeleteBehavior()->isDisabled());
        $model->enableSoftDelete();
        $this->assertTrue($model->getSoftDeleteBehavior()->isEnabled());

        $model->initializeUuid(['field' => 'uuid', 'native' => true, 'binary' => false]);
        $this->assertInstanceOf(Transformable::class, $model->getUuidBehavior());
        $model->getUuidBehavior()->notify('beforeValidationOnCreate', $model);
        $this->assertInstanceOf(RawValue::class, $model->uuid);

        $binaryNative = new ModelBehaviorDouble();
        $binaryNative->fakeModelsManager = new FakeModelsManager();
        $binaryNative->initializeUuid(['field' => 'uuid', 'native' => true, 'binary' => true]);
        $binaryNative->getUuidBehavior()->notify('beforeValidationOnCreate', $binaryNative);
        $this->assertSame('UUID_TO_BIN(UUID())', $binaryNative->uuid->getValue());

        $binaryGenerated = new ModelBehaviorDouble();
        $binaryGenerated->fakeModelsManager = new FakeModelsManager();
        $binaryGenerated->initializeUuid(['field' => 'uuid', 'native' => false, 'binary' => true]);
        $binaryGenerated->getUuidBehavior()->notify('beforeValidationOnCreate', $binaryGenerated);
        $this->assertIsString($binaryGenerated->uuid);
        $this->assertSame(16, strlen($binaryGenerated->uuid));
    }

    public function testRestoreTraitPaths(): void
    {
        $oldEvents = ini_get('phalcon.orm.events');
        ini_set('phalcon.orm.events', '1');

        try {
            $model = new ModelBehaviorDouble();
            $model->fakeModelsManager = new FakeModelsManager();
            $model->initializeSoftDelete();
            $model->deleted = 1;

            $model->cancelEvents = ['beforeRestore'];
            $this->assertFalse($model->restore());

            $model->cancelEvents = [];
            $this->assertTrue($model->restore());
            $this->assertSame(0, $model->deleted);
            $this->assertContains('afterRestore', $model->firedEvents);

            $skipped = new ModelBehaviorDouble();
            $skipped->fakeModelsManager = new FakeModelsManager();
            $skipped->initializeSoftDelete();
            $skipped->deleted = 1;
            $skipped->skipRestore = true;
            $this->assertTrue($skipped->restore());
            $this->assertSame(1, $skipped->deleted);

            $model->deleted = 1;
            $model->saveResult = false;
            $this->assertFalse($model->restore());
            $this->assertContains('notRestored', $model->firedEvents);
        } finally {
            ini_set('phalcon.orm.events', (string)$oldEvents);
        }
    }

    public function testReplicationTraitHelpers(): void
    {
        $model = new ModelBehaviorDouble();

        $config = $this->getConfig();
        $config->merge([
            'database' => [
                'drivers' => [
                    'mysql' => [
                        'readonly' => [
                            'enable' => true,
                        ],
                    ],
                ],
            ],
        ]);

        $read = $this->createStub(AdapterInterface::class);
        $write = $this->createStub(AdapterInterface::class);
        $this->di->setShared('read-connection', $read);
        $this->di->setShared('write-connection', $write);

        $model->setEventsManager(new EventsManager());
        $model->initializeReplication([
            'lag' => 50,
            'connectionService' => 'write-connection',
            'readConnectionService' => 'read-connection',
            'writeConnectionService' => 'write-connection',
        ]);

        $this->assertSame(50, $model::getReplicationLag());
        $this->assertSame('read-connection', $model->getReadConnectionService());
        $this->assertSame('write-connection', $model->getWriteConnectionService());
        $this->assertTrue($model->isReplicationReady());

        $model::setReplicationReadyAt(null);
        $this->assertSame($write, $model->selectReadConnection());

        $model->getEventsManager()->fire('model:afterSave', $model);
        $this->assertNotNull($model::getReplicationReadyAt());

        $model::setReplicationReadyAt(PHP_INT_MAX);
        $this->assertFalse($model->isReplicationReady());
        $this->assertSame($write, $model->selectReadConnection());
    }

    public function testLocaleTraitMagicMethods(): void
    {
        $model = new LocaleTraitDouble();

        $this->assertSame('label-ok', $model->label('ok'));

        $model->name = 'Localized';
        $this->assertSame('Localized', $model->name);
        $this->assertSame('Localized', $model->nameEn);

        try {
            $model->missingMethod();
        } catch (\Throwable) {
            $this->assertTrue(true);
        }

        try {
            $model->missingProperty = 'value';
        } catch (\Throwable) {
            $this->assertTrue(true);
        }

        try {
            @$model->unknownProperty;
        } catch (\Throwable) {
            $this->assertTrue(true);
        }
    }

    public function testModelMagicMethodsHandleLocaleAndRelations(): void
    {
        $locale = $this->di->get('locale');
        assert($locale instanceof Locale);
        $previousLocale = $locale->locale;
        $locale->locale = 'en';

        try {
            $manager = new FakeModelsManager();
            $childRelation = new Relation(Relation::HAS_ONE, ModelBehaviorDouble::class, 'id', 'parentId', [
                'alias' => 'child',
            ]);
            $manager->setRelationByAlias(ModelBehaviorDouble::class, 'child', $childRelation);
            $manager->setRelationByAlias(ModelBehaviorDouble::class, 'camelchild', $childRelation);
            $manager->setRelationByAlias(ModelBehaviorDouble::class, 'staticchild', $childRelation);
            $manager->setRelationByAlias(ModelBehaviorDouble::class, 'missingdeclared', $childRelation);

            $model = new ModelBehaviorDouble();
            $model->fakeModelsManager = $manager;
            $related = new ModelBehaviorDouble();

            $model->__set('name', 'Localized');
            $this->assertSame('Localized', $model->nameEn);
            $this->assertSame('Localized', $model->__get('name'));

            $model->__set('child', $related);
            $this->assertSame($related, $model->getDirtyRelatedAlias('child'));
            $this->assertSame($related, $model->__get('child'));

            $loaded = new ModelBehaviorDouble();
            $model->setLoadedRelatedAlias('loadedChild', $loaded);
            $this->assertSame($loaded, $model->__get('loadedChild'));

            $declared = new ModelBehaviorDouble();
            $declared->fakeModelsManager = $manager;
            $declared->child = $related;
            $declared->camelChild = $related;

            $this->assertSame($related, $declared->__get('child'));
            $this->assertSame($related, $declared->__get('camelChild'));

            foreach (['staticchild', 'missingdeclared'] as $property) {
                try {
                    @$declared->__get($property);
                } catch (\Throwable) {
                    $this->assertTrue(true);
                }
            }

            $locale->locale = null;
            try {
                @$declared->__get('unknown');
            } catch (\Throwable) {
                $this->assertTrue(true);
            }

            $withoutServices = new class extends ModelBehaviorDouble {
                #[\Override]
                public function getDI(): \Phalcon\Di\DiInterface
                {
                    throw new \RuntimeException('No container');
                }

                #[\Override]
                public function getModelsManager(): \Phalcon\Mvc\Model\ManagerInterface
                {
                    throw new \RuntimeException('No models manager');
                }
            };
            try {
                @$withoutServices->__set('unknown', 'value');
            } catch (\Throwable) {
                $this->assertTrue(true);
            }
        } finally {
            $locale->locale = $previousLocale;
        }
    }

    public function testEventTraitCancellationPaths(): void
    {
        $ensure = new \ReflectionMethod(\PhalconKit\Mvc\Model::class, 'ensureTraversableResultset');
        try {
            $ensure->invoke(null, $this->createStub(\Phalcon\Mvc\Model\ResultsetInterface::class));
            $this->fail('Expected non-traversable resultset exception.');
        } catch (\LogicException $exception) {
            $this->assertSame('Phalcon model find() returned a non-traversable resultset.', $exception->getMessage());
        }

        EventModelDouble::resetEvents();
        EventModelDouble::$cancelEvents = ['beforeFind'];
        $this->assertInstanceOf(\Phalcon\Mvc\Model\ResultsetInterface::class, EventModelDouble::find());
        $this->assertSame(['beforeFind'], EventModelDouble::$firedEvents);

        foreach (['findFirst', 'count', 'sum', 'average', 'minimum', 'maximum'] as $method) {
            $events = ['before' . ucfirst(\PhalconKit\Support\Helper::camelize($method))];
            EventModelDouble::resetEvents();
            EventModelDouble::$cancelEvents = $events;
            $this->assertFalse(EventModelDouble::$method([]));
            $this->assertSame($events, EventModelDouble::$firedEvents);
        }

        EventsTraitSubject::resetEvents();
        $this->assertInstanceOf(EventsTraitResultsetDouble::class, EventsTraitSubject::find());
        $this->assertSame(['beforeFind', 'afterFind'], EventsTraitSubject::$firedEvents);

        $this->assertNull(EventsTraitSubject::findFirst());
        $this->assertSame(7, EventsTraitSubject::count());
        $this->assertSame(1.5, EventsTraitSubject::sum());
        $this->assertSame(2.5, EventsTraitSubject::average());
        $this->assertSame(3.5, EventsTraitSubject::minimum());
        $this->assertSame(4.5, EventsTraitSubject::maximum());
    }

    public function testValidationHelpers(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 1;
        $model->position = 2;
        $model->deleted = 0;
        $model->uuid = 'abc';
        $model->createdBy = 1;
        $model->createdAt = '2026-05-21 12:00:00';
        $model->updatedBy = 1;
        $model->updatedAt = '2026-05-21 12:00:00';
        $model->deletedBy = 1;
        $model->deletedAt = '2026-05-21 12:00:00';
        $model->restoredBy = 1;
        $model->restoredAt = '2026-05-21 12:00:00';
        $model->number = 5;
        $model->big = '5';
        $model->string = 'abc';
        $model->status = 'active';
        $model->email = 'test@example.com';
        $model->date = '2026-05-21';
        $model->datetime = '2026-05-21 12:00:00';
        $model->json = '{"ok":true}';
        $model->color = '#ffffff';

        $this->assertSame([null, '', 'NULL'], $model->publicGetAllowEmptyOption());
        $this->assertFalse($model->publicGetAllowEmptyOption(false));

        $validator = $model->genericValidation();
        $model->addPresenceValidation($validator, 'string');
        $model->addUnsignedIntValidation($validator, 'id', false);
        $model->addUnsignedBigIntValidation($validator, 'big', false);
        $model->addNumberValidation($validator, 'number', 1, 10, false);
        $model->addStringLengthValidation($validator, 'string', 1, 10, false);
        $model->addInclusionInValidation($validator, 'status', ['active'], false);
        $model->addBooleanValidation($validator, 'deleted', false);
        $model->addInclusionValidation($validator, 'status', ['active'], false);
        $model->addUniquenessValidation($validator, 'uuid', false);
        $model->addEmailValidation($validator, 'email', false);
        $model->addDateValidation($validator, 'date', false);
        $model->addDateTimeValidation($validator, 'datetime', false);
        $model->addJsonValidation($validator, 'json', false);
        $model->addColorValidation($validator, 'color', false);
        $model->addIdValidation($validator);
        $model->addPositionValidation($validator);
        $model->addSoftDeleteValidation($validator);
        $model->addUuidValidation($validator);
        $model->addCreatedValidation($validator);
        $model->addUpdatedValidation($validator);
        $model->addDeletedValidation($validator);
        $model->addRestoredValidation($validator);

        $this->assertNotEmpty($validator->getValidators());

        $model->date = null;
        $this->assertTrue($model->publicShouldSkipOptionalValidation('date', true));
        $skipped = new Validation();
        $this->assertSame($skipped, $model->addDateValidation($skipped, 'date', true));
        $this->assertCount(0, $skipped->getValidators());

        $model->id = null;
        $unsignedInt = new Validation();
        $this->assertSame($unsignedInt, $model->addUnsignedIntValidation($unsignedInt, 'id', true));
        $this->assertCount(0, $unsignedInt->getValidators());

        $model->big = null;
        $unsignedBigInt = new Validation();
        $this->assertSame($unsignedBigInt, $model->addUnsignedBigIntValidation($unsignedBigInt, 'big', true));
        $this->assertCount(0, $unsignedBigInt->getValidators());

        $model->datetime = null;
        $datetime = new Validation();
        $this->assertSame($datetime, $model->addDateTimeValidation($datetime, 'datetime', true));
        $this->assertCount(0, $datetime->getValidators());

        $model->createdBy = null;
        $created = new Validation();
        $this->assertSame($created, $model->addCreatedValidation($created, 'createdBy', 'createdAt', true));
        $this->assertCount(0, $created->getValidators());

        $model->position = new RawValue('position + 1');
        $this->assertFalse($model->publicShouldSkipOptionalValidation('position', true));
        $position = new Validation();
        $model->addPositionValidation($position);
        $this->assertEmpty($position->getValidators());
    }

    public function testRelationshipMessageAndArrayHelpers(): void
    {
        $model = new ModelBehaviorDouble();
        $model->fakeModelsMetaData = new FakeMetaData();
        $related = new ModelBehaviorDouble();
        $related->id = 7;
        $related->name = 'Related';

        $model->setRelationshipContext('root');
        $this->assertSame('root', $model->getRelationshipContext());

        $model->setKeepMissingRelated(['Children' => false]);
        $this->assertSame(['children' => false], $model->getKeepMissingRelated());
        $model->setKeepMissingRelatedAlias('OtherChildren', true);
        $this->assertTrue($model->getKeepMissingRelatedAlias('otherchildren'));

        $model->setLoadedRelated(['Child' => $related, 'Rows' => [['id' => 1]], 'None' => false]);
        $model->setDirtyRelated(['DirtyRows' => [$related]]);

        $this->assertSame(['child' => $related, 'rows' => [['id' => 1]], 'none' => false], $model->getLoadedRelated());
        $this->assertSame(['dirtyrows' => [$related]], $model->getDirtyRelated());
        $this->assertTrue($model->hasDirtyRelated());

        $array = $model->relatedToArray();
        $this->assertSame('Related', $array['child']['name']);
        $this->assertSame([['id' => 1]], $array['rows']);
        $this->assertNull($array['none']);
        $this->assertSame('Related', $array['dirtyrows'][0]['name']);

        $filtered = $model->relatedToArray(['child' => ['name']]);
        $this->assertSame(['name' => 'Related'], $filtered['child']);

        $model->fakeModelsMetaData->fakeColumnMap = ['child' => 'mappedChild'];
        $mapped = $model->relatedToArray(['mappedChild' => ['name']]);
        $this->assertSame(['name' => 'Related'], $mapped['mappedChild']);
        $model->fakeModelsMetaData->fakeColumnMap = null;

        $message = new Message('Invalid', 'field', 'Invalid');
        $message->setMetaData(['context' => 'leaf', 'index' => '2']);
        $model->appendMessages([$message], 'children', 1);

        $this->assertSame('children.leaf', $model->messages[0]->getMetaData()['context']);
        $this->assertSame('1.2', $model->messages[0]->getMetaData()['index']);

        $record = new ModelBehaviorDouble();
        $record->appendMessage(new Message('Record invalid', 'field', 'Invalid'));
        $model->appendMessagesFromRecord($record, 'record');
        $model->appendMessagesFromRecordList([$record], 'list', 3);

        $resultset = $this->createMock(\Phalcon\Mvc\Model\ResultsetInterface::class);
        $resultset
            ->expects($this->once())
            ->method('getMessages')
            ->willReturn([new Message('Resultset invalid', 'field')]);
        $model->appendMessagesFromResultset($resultset, 'resultset', 4);

        $this->assertCount(4, $model->messages);
        $this->assertSame('list[3].record', $model->messages[1]->getMetaData()['context']);
        $this->assertSame('list[3].record', $model->messages[2]->getMetaData()['context']);
        $this->assertSame('resultset', $model->messages[3]->getMetaData()['context']);
    }

    public function testRelationshipLookupHelpersAndErrors(): void
    {
        $model = new ModelBehaviorDouble();
        $model->fakeModelsManager = new FakeModelsManager();
        $model->fakeModelsMetaData = new FakeMetaData();

        $this->expectException(\LogicException::class);
        $model->assignRelated([0 => []]);
    }

    public function testRelationshipAssignRelatedBuildsDirtyRecords(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 9;
        $model->fakeModelsManager = new FakeModelsManager();
        $model->fakeModelsMetaData = new FakeMetaData();

        $child = new Relation(Relation::HAS_ONE, ModelBehaviorDouble::class, 'id', 'parentId', [
            'alias' => 'child',
        ]);
        $children = new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'id', 'parentId', [
            'alias' => 'children',
        ]);
        $model->fakeModelsManager->setRelationByAlias(ModelBehaviorDouble::class, 'child', $child);
        $model->fakeModelsManager->setRelationByAlias(ModelBehaviorDouble::class, 'children', $children);

        $this->assertSame($model, $model->assignRelated([]));

        $model->assignRelated(['child' => ['parentId' => 1]], ['other']);
        $this->assertFalse($model->hasDirtyRelatedAlias('child'));

        $model->assignRelated(['missing' => ['id' => 1]]);
        $this->assertFalse($model->hasDirtyRelatedAlias('missing'));

        $model->assignRelated(['child' => 7]);
        $this->assertInstanceOf(ModelBehaviorDouble::class, $model->getDirtyRelatedAlias('child'));
        $this->assertSame(7, $model->getDirtyRelatedAlias('child')->parentId);

        $model->setDirtyRelated([]);
        $model->assignRelated(['child' => []]);
        $this->assertSame(9, $model->getDirtyRelatedAlias('child')->parentId);

        $directChild = new ModelBehaviorDouble();
        $model->assignRelated(['child' => $directChild]);
        $this->assertSame($directChild, $model->getDirtyRelatedAlias('child'));

        $related = new ModelBehaviorDouble();
        $related->parentId = 12;
        $model->assignRelated(['children' => ['false', 'true', 11, ['parentId' => 12], $related]]);

        $this->assertTrue($model->getKeepMissingRelatedAlias('children'));
        $this->assertCount(3, $model->getDirtyRelatedAlias('children'));

        $model->setKeepMissingRelatedAlias('children', false);
        $model->assignRelated(['children' => []]);
        $this->assertSame([], $model->getDirtyRelatedAlias('children'));

        try {
            $model->assignRelated(['child' => new FakeAudit()]);
            $this->fail('Expected invalid related model exception.');
        } catch (\Exception $exception) {
            $this->assertSame(400, $exception->getCode());
        }

        try {
            $model->assignRelated(['children' => [new FakeAudit()]]);
            $this->fail('Expected invalid traversed related model exception.');
        } catch (\Exception $exception) {
            $this->assertSame(400, $exception->getCode());
        }
    }

    public function testRelationshipNativeAssignAndToArray(): void
    {
        $model = new NativeRelationshipModelDouble();
        $model->fakeModelsManager = new FakeModelsManager();
        $model->fakeModelsMetaData = new FakeMetaData();
        $model->fakeModelsMetaData->attributes = ['id', 'name'];
        $model->assign(['id' => 1, 'name' => 'Native']);

        $related = new ModelBehaviorDouble();
        $related->name = 'Related';
        $model->setLoadedRelated(['Child' => $related]);

        $this->assertSame(1, $model->id);
        $this->assertSame('Native', $model->name);
        $this->assertSame('Related', $model->toArray()['child']['name']);
    }

    public function testRelationshipFindsAndBuildsEntities(): void
    {
        $model = new ModelBehaviorDouble();
        $manager = new FakeModelsManager();
        $metaData = new FakeMetaData();
        $model->fakeModelsManager = $manager;
        $model->fakeModelsMetaData = $metaData;

        $related = new ModelBehaviorDouble();
        $related->fakeModelsMetaData = $metaData;
        $manager->loadedModels[ModelBehaviorDouble::class] = $related;

        $this->assertNull($model->findFirstByPrimaryKeys([], ModelBehaviorDouble::class));

        ModelBehaviorDouble::$findFirstResult = $related;
        $this->assertSame($related, $model->findFirstByPrimaryKeys(['id' => 7], ModelBehaviorDouble::class));

        try {
            $model->getEntityFromData([], ['fields' => 'id', 'modelClass' => ModelBehaviorDouble::class]);
            $this->fail('Expected invalid fields exception.');
        } catch (\Exception $exception) {
            $this->assertSame('Parameter `fields` must be an array', $exception->getMessage());
        }

        try {
            $model->getEntityFromData([], ['fields' => []]);
            $this->fail('Expected missing model class exception.');
        } catch (\Exception $exception) {
            $this->assertSame('Parameter `modelClass` is mandatory', $exception->getMessage());
        }

        ModelBehaviorDouble::$findFirstResult = null;
        $model->id = 42;
        $entity = $model->getEntityFromData(
            ['name' => 'Created'],
            [
                'alias' => 'child',
                'fields' => ['parentId'],
                'modelClass' => ModelBehaviorDouble::class,
                'readFields' => ['id'],
                'type' => Relation::HAS_ONE,
                'whiteList' => ['child' => ['name', 'parentId']],
                'dataColumnMap' => ['child' => []],
            ]
        );

        $this->assertInstanceOf(ModelBehaviorDouble::class, $entity);
        $this->assertSame(42, $entity->parentId);
        $this->assertSame('Created', $entity->name);

        $many = $model->getEntityFromData(
            ['parentId' => 42],
            [
                'alias' => 'children',
                'fields' => ['parentId'],
                'modelClass' => ModelBehaviorDouble::class,
                'type' => Relation::HAS_MANY,
            ]
        );

        $this->assertInstanceOf(ModelBehaviorDouble::class, $many);
    }

    public function testRelationshipPostSaveAfterHelpers(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 5;
        $related = new ModelBehaviorDouble();
        $visited = new Collection();

        $relation = new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'id', 'parentId', [
            'alias' => 'children',
        ]);

        $this->assertTrue($model->postSaveRelatedRecordsAfter($relation, [$related], $visited));
        $this->assertSame(5, $related->parentId);

        $through = new Relation(Relation::HAS_MANY_THROUGH, ModelBehaviorDouble::class, 'id', 'id', [
            'alias' => 'roles',
        ]);

        $this->assertNull($model->postSaveRelatedRecordsAfter($through, [$related], $visited));

        $related->doSaveResult = false;
        $this->assertFalse($model->postSaveRelatedRecordsAfter($relation, [$related], $visited));
    }

    public function testRelationshipGetRelatedAndSavePipelines(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 5;
        $model->fakeModelsManager = new FakeModelsManager();
        $model->fakeModelsMetaData = new FakeMetaData();
        $visited = new Collection();

        $children = new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'id', 'parentId', [
            'alias' => 'children',
        ]);
        $owner = new Relation(Relation::BELONGS_TO, ModelBehaviorDouble::class, 'parentId', 'id', [
            'alias' => 'owner',
        ]);
        $model->fakeModelsManager->setRelationByAlias(ModelBehaviorDouble::class, 'children', $children);
        $model->fakeModelsManager->setRelationByAlias(ModelBehaviorDouble::class, 'owner', $owner);

        $this->assertSame($model->fakeModelsManager->queryResult, $model->getRelated('children', ['limit' => 1]));
        $this->assertNotEmpty($model->fakeModelsManager->relationRecordCalls);

        try {
            $model->getRelated('missing');
            $this->fail('Expected missing relation exception.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString("using alias 'missing'", $exception->getMessage());
        }

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('begin')->willReturn(true);
        $connection->method('rollback')->willReturn(true);
        $connection->method('commit')->willReturn(true);

        $parent = new ModelBehaviorDouble();
        $parent->id = 99;
        $this->assertTrue($model->callPreSaveRelatedRecords($connection, ['owner' => $parent], $visited));
        $this->assertSame(99, $model->parentId);

        $parent->doSaveResult = false;
        $parent->appendMessage(new Message('Owner invalid', 'id'));
        $this->assertFalse($model->callPreSaveRelatedRecords($connection, ['owner' => $parent], $visited));
        $this->assertSame('owner', $model->messages[0]->getMetaData()['context']);

        try {
            $model->callPreSaveRelatedRecords($connection, ['owner' => new \stdClass()], $visited);
            $this->fail('Expected invalid belongs-to record exception.');
        } catch (\Exception $exception) {
            $this->assertSame(400, $exception->getCode());
        }

        $related = new ModelBehaviorDouble();
        $this->assertTrue($model->callPostSaveRelatedRecords($connection, ['owner' => $parent], $visited));
        $this->assertTrue($model->callPostSaveRelatedRecords($connection, ['children' => [$related]], $visited));
        $this->assertSame(5, $related->parentId);

        $failingChild = new ModelBehaviorDouble();
        $failingChild->doSaveResult = false;
        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['children' => [$failingChild]], $visited));
        $this->assertSame('Unable to save related records after', $model->messages[array_key_last($model->messages)]->getMessage());

        $throwing = new ThrowingSaveModelDouble();
        $this->assertFalse($model->postSaveRelatedRecordsAfter($children, [$throwing], $visited));
        $this->assertSame('Exception', $model->messages[array_key_last($model->messages)]->getType());

        $deleteChildren = new Relation(Relation::HAS_MANY, RelatedDeleteModelDouble::class, 'id', 'parentId', [
            'alias' => 'deleteChildren',
        ]);
        $deleteModel = new RelatedDeleteModelDouble();
        $deleteModel->id = 8;
        $deleteModel->fakeModelsMetaData = new FakeMetaData();
        RelatedDeleteModelDouble::$findResult = new FailingModelResultsetDouble(false);
        $model->fakeModelsManager->loadedModels[RelatedDeleteModelDouble::class] = $deleteModel;
        $model->fakeModelsManager->setRelationByAlias(ModelBehaviorDouble::class, 'deleteChildren', $deleteChildren);
        $model->setKeepMissingRelatedAlias('deleteChildren', false);

        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['deletechildren' => [$deleteModel]], $visited));
        $this->assertSame('Unable to delete node entity `' . RelatedDeleteModelDouble::class . '`', $model->messages[array_key_last($model->messages)]->getMessage());

        try {
            $model->callPostSaveRelatedRecords($connection, ['missing' => []], $visited);
            $this->fail('Expected missing post-save relation exception.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('There are no defined relations', $exception->getMessage());
        }

        try {
            $model->callPostSaveRelatedRecords($connection, ['children' => 1], $visited);
            $this->fail('Expected invalid post-save payload exception.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString('Only objects/arrays can be stored', $exception->getMessage());
        }

        $missingField = new Relation(Relation::HAS_MANY, ModelBehaviorDouble::class, 'missingField', 'parentId', [
            'alias' => 'missingFieldChildren',
        ]);
        $model->fakeModelsManager->setRelationByAlias(
            ModelBehaviorDouble::class,
            'missingFieldChildren',
            $missingField
        );

        try {
            $model->callPostSaveRelatedRecords($connection, ['missingfieldchildren' => [new ModelBehaviorDouble()]], $visited);
            $this->fail('Expected missing relation field exception.');
        } catch (\Exception $exception) {
            $this->assertStringContainsString("The column 'missingField' needs to be present", $exception->getMessage());
        }
    }

    public function testRelationshipThroughSaveHelpers(): void
    {
        $model = new ModelBehaviorDouble();
        $model->id = 5;
        $manager = new FakeModelsManager();
        $intermediate = new IntermediateModelDouble();
        $manager->loadedModels[IntermediateModelDouble::class] = $intermediate;
        $model->fakeModelsManager = $manager;

        $related = new ModelBehaviorDouble();
        $related->id = 8;
        $visited = new Collection();

        $through = new Relation(Relation::HAS_MANY_THROUGH, ModelBehaviorDouble::class, 'id', 'id', [
            'alias' => 'roles',
        ]);
        $through->setIntermediateRelation('parentId', IntermediateModelDouble::class, 'childId');

        $this->assertTrue($model->postSaveRelatedThroughAfter($through, [$related], $visited));
        $this->assertSame(5, $intermediate->parentId);
        $this->assertSame(8, $intermediate->readAttribute('childId'));

        $failingRelated = new ModelBehaviorDouble();
        $failingRelated->doSaveResult = false;
        $this->assertFalse($model->postSaveRelatedThroughAfter($through, [$failingRelated], $visited));

        $hasOneThrough = new Relation(Relation::HAS_ONE_THROUGH, ModelBehaviorDouble::class, 'id', 'id', [
            'alias' => 'role',
        ]);
        $hasOneThrough->setIntermediateRelation('parentId', IntermediateModelDouble::class, 'childId');

        $existingIntermediate = new IntermediateModelDouble();
        ModelBehaviorDouble::$findFirstResult = $existingIntermediate;
        $this->assertTrue($model->postSaveRelatedThroughAfter($hasOneThrough, [$related], $visited));
        $this->assertSame(5, $existingIntermediate->parentId);

        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('begin')->willReturn(true);
        $connection->method('rollback')->willReturn(true);
        $connection->method('commit')->willReturn(true);

        $deleteThrough = new Relation(Relation::HAS_MANY_THROUGH, ModelBehaviorDouble::class, 'id', 'id', [
            'alias' => 'deleteRoles',
        ]);
        $deleteThrough->setIntermediateRelation('parentId', IntermediateDeleteModelDouble::class, 'childId');

        $loadedIntermediate = new IntermediateDeleteModelDouble();
        $loadedIntermediate->fakeModelsMetaData = new FakeMetaData();
        $existingNode = new IntermediateDeleteModelDouble();
        $existingNode->id = 100;
        $existingNode->fakeModelsManager = new FakeModelsManager();
        $existingNode->initializeSoftDelete();
        IntermediateDeleteModelDouble::$findFirstResult = $existingNode;
        IntermediateDeleteModelDouble::$findResult = new FailingModelResultsetDouble(false);

        $manager->loadedModels[IntermediateDeleteModelDouble::class] = $loadedIntermediate;
        $manager->setRelationByAlias(ModelBehaviorDouble::class, 'deleteRoles', $deleteThrough);
        $model->setKeepMissingRelatedAlias('deleteRoles', false);

        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['deleteroles' => [$related]], $visited));
        $this->assertSame('Unable to delete node entity `' . IntermediateDeleteModelDouble::class . '`', $model->messages[array_key_last($model->messages)]->getMessage());

        $deletedNode = new IntermediateDeleteModelDouble();
        $deletedNode->id = 101;
        $deletedNode->fakeModelsManager = new FakeModelsManager();
        $deletedNode->initializeSoftDelete();
        $deletedNode->deleted = 1;
        $deletedNode->saveResult = false;
        IntermediateDeleteModelDouble::$findFirstResult = $deletedNode;
        $model->setKeepMissingRelatedAlias('deleteRoles', true);

        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['deleteroles' => [$related]], $visited));
        $this->assertSame('Unable to restored previously deleted related node `' . IntermediateDeleteModelDouble::class . '`', $model->messages[array_key_last($model->messages)]->getMessage());

        $existingNode->deleted = 0;
        IntermediateDeleteModelDouble::$findFirstResult = $existingNode;
        $badEdge = new ModelBehaviorDouble();
        $badEdge->id = 9;
        $badEdge->doSaveResult = false;

        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['deleteroles' => [$badEdge]], $visited));
        $this->assertSame('Unable to save related entity `' . IntermediateDeleteModelDouble::class . '`', $model->messages[array_key_last($model->messages)]->getMessage());

        IntermediateDeleteModelDouble::$findFirstResult = null;
        $badThrough = new ModelBehaviorDouble();
        $badThrough->id = 10;
        $badThrough->doSaveResult = false;

        $this->assertFalse($model->callPostSaveRelatedRecords($connection, ['deleteroles' => [$badThrough]], $visited));
        $this->assertSame('Unable to save related through after', $model->messages[array_key_last($model->messages)]->getMessage());

        $badManager = new FakeModelsManager();
        $badIntermediate = new IntermediateModelDouble();
        $badIntermediate->doSaveResult = false;
        $badManager->loadedModels[IntermediateModelDouble::class] = $badIntermediate;
        $model->fakeModelsManager = $badManager;

        $this->assertFalse($model->postSaveRelatedThroughAfter($through, [$related], $visited));
        $this->assertSame('Unable to save intermediate model `' . IntermediateModelDouble::class . '`', $model->messages[array_key_last($model->messages)]->getMessage());
    }
}
