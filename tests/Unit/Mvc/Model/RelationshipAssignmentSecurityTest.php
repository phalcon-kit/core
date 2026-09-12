<?php

declare(strict_types=1);

namespace PhalconKit\Tests\Unit\Mvc\Model;

use Phalcon\Di\Di;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\Model\Relation;
use PhalconKit\Config\Config;
use PhalconKit\Exception\InvalidArgumentException;
use PhalconKit\Support\Helper;
use PhalconKit\Support\HelperFactory;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeMetaData;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\FakeModelsManager;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\NativeRelationshipModelDouble;
use PhalconKit\Tests\Unit\Mvc\Model\Fixtures\RelationshipAssignmentModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RelationshipAssignmentSecurityTest extends TestCase
{
    private ?DiInterface $previousDi;
    private mixed $previousHelperFactory;
    private FakeModelsManager $manager;
    private FakeMetaData $metadata;

    protected function setUp(): void
    {
        $this->previousDi = Di::getDefault();
        $this->previousHelperFactory = Helper::$helperFactory;
        Helper::$helperFactory = null;
        $di = new \PhalconKit\Di\Di();
        Di::setDefault($di);
        $di->setShared('config', new Config());
        $di->setShared('helper', new HelperFactory());
        $this->manager = new FakeModelsManager();
        $this->metadata = new FakeMetaData();
        $this->metadata->attributes = ['id', 'tenantId', 'parentId', 'parentTenantId', 'name', 'secret'];
        $di->setShared('modelsManager', $this->manager);
        $di->setShared('modelsMetadata', $this->metadata);
        NativeRelationshipModelDouble::$defaultModelsManager = $this->manager;
        NativeRelationshipModelDouble::$defaultModelsMetaData = $this->metadata;
    }

    protected function tearDown(): void
    {
        RelationshipAssignmentModel::$lookup = null;
        RelationshipAssignmentModel::$lookups = [];
        NativeRelationshipModelDouble::$defaultModelsManager = null;
        NativeRelationshipModelDouble::$defaultModelsMetaData = null;
        Helper::$helperFactory = $this->previousHelperFactory;
        Di::reset();
        if ($this->previousDi !== null) {
            Di::setDefault($this->previousDi);
        }
    }

    #[DataProvider('foreignLookupProvider')]
    public function testForeignDirectRecordIsRejectedBeforeAssignment(int $type, array $payload): void
    {
        $parent = $this->parentWithRelation($type);
        $foreign = $this->record(['id' => 10, 'parentId' => 7, 'name' => 'Original', 'secret' => 'private']);
        RelationshipAssignmentModel::$lookup = static fn () => $foreign;

        try {
            $parent->assign(['child' => $payload + ['name' => 'Tampered']], ['child' => ['name', 'parentId']]);
            self::fail('Expected rejection before the foreign record is mutated or exposed as a relation.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(400, $exception->getCode());
        }

        self::assertSame('Original', $foreign->name);
        self::assertSame(7, $foreign->parentId);
        self::assertFalse($parent->hasDirtyRelatedAlias('child'));
        self::assertFalse($parent->hasLoadedRelatedAlias('child'));
    }

    public static function foreignLookupProvider(): array
    {
        return [
            'has-one primary key' => [Relation::HAS_ONE, ['id' => 10, 'parentId' => 5]],
            'has-many primary key' => [Relation::HAS_MANY, ['id' => 10, 'parentId' => 5]],
            'has-one relationship key' => [Relation::HAS_ONE, ['parentId' => 7]],
            'has-many relationship key' => [Relation::HAS_MANY, ['parentId' => 7]],
        ];
    }

    public function testPrimaryKeyMissCannotBypassFallbackOwnershipCheck(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE);
        $foreign = $this->record(['id' => 10, 'parentId' => 7, 'name' => 'Original']);
        RelationshipAssignmentModel::$lookup = static fn (array $query) => $query['bind'] === [7] ? $foreign : null;

        try {
            $parent->assign(['child' => ['id' => 99, 'parentId' => 7, 'name' => 'Tampered']]);
            self::fail('Expected rejection after the primary-key miss and foreign relation-key hit.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(400, $exception->getCode());
        }

        self::assertCount(2, RelationshipAssignmentModel::$lookups);
        self::assertSame(10, $foreign->id);
        self::assertSame('Original', $foreign->name);
        self::assertFalse($parent->hasDirtyRelated());
    }

    public function testCompositePrimaryKeyTargetsTheNamedRecordRegardlessOfPayloadOrder(): void
    {
        $this->metadata->primaryKeyAttributes = ['tenantId', 'id'];
        $parent = $this->parentWithRelation(Relation::BELONGS_TO);
        $intended = $this->record(['tenantId' => 5, 'id' => 7, 'name' => 'Intended']);
        $other = $this->record(['tenantId' => 7, 'id' => 5, 'name' => 'Other tenant']);
        RelationshipAssignmentModel::$lookup = static fn (array $query) => $query['bind'] === [5, 7] ? $intended : $other;

        $parent->assign(['child' => ['id' => 7, 'name' => 'Updated', 'tenantId' => 5]], ['child' => ['name']]);

        self::assertSame($intended, $parent->getDirtyRelatedAlias('child'));
        self::assertSame('Updated', $intended->name);
        self::assertSame('Other tenant', $other->name);
    }

    public function testCompositeRelationKeyCannotSelectAndReparentTheSwappedRecord(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE, ['tenantId', 'id'], ['parentTenantId', 'parentId']);
        $parent->tenantId = 7;
        $owned = $this->record(['id' => 10, 'parentTenantId' => 7, 'parentId' => 5, 'name' => 'Owned']);
        $foreign = $this->record(['id' => 11, 'parentTenantId' => 5, 'parentId' => 7, 'name' => 'Foreign']);
        RelationshipAssignmentModel::$lookup = static fn (array $query) => $query['bind'] === [7, 5] ? $owned : $foreign;

        $parent->assign(['child' => ['parentId' => 5, 'name' => 'Updated', 'parentTenantId' => 7]]);

        self::assertSame($owned, $parent->getDirtyRelatedAlias('child'));
        self::assertSame('Updated', $owned->name);
        self::assertSame('Foreign', $foreign->name);
        self::assertSame(5, $foreign->parentTenantId);
        self::assertSame(7, $foreign->parentId);
    }

    public function testFallbackCannotHideForeignOwnershipThroughMappedAssignment(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE);
        $foreign = $this->record(['id' => 10, 'parentId' => 7, 'name' => 'Original']);
        RelationshipAssignmentModel::$lookup = static fn () => $foreign;

        try {
            $parent->assign(
                ['child' => ['parentId' => 7, 'owner' => 5, 'label' => 'Tampered']],
                ['child' => ['parentId', 'name']],
                ['child' => ['owner' => 'parentId', 'label' => 'name']]
            );
            self::fail('Expected stored ownership to be checked before mapped assignment.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(400, $exception->getCode());
        }

        self::assertSame(7, $foreign->parentId);
        self::assertSame('Original', $foreign->name);
        self::assertFalse($parent->hasDirtyRelated());
    }

    public function testUnownedFallbackRespectsDisabledAdoption(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE);
        $parent->setRelationshipOptions([
            'enforceDirectOwnership' => true,
            'allowUnownedDirectRelationAdoption' => false,
        ]);
        $unowned = $this->record(['id' => 10, 'name' => 'Original']);
        RelationshipAssignmentModel::$lookup = static fn () => $unowned;

        try {
            $parent->assign(['child' => ['name' => 'Tampered']]);
            self::fail('Expected the adoption policy to apply to fallback lookups.');
        } catch (InvalidArgumentException $exception) {
            self::assertSame(400, $exception->getCode());
        }

        self::assertSame('Original', $unowned->name);
        self::assertNull($unowned->parentId);
        self::assertFalse($parent->hasDirtyRelated());
    }

    public function testMissingPrimaryAndRelationKeysNeverFetchAnArbitraryRecord(): void
    {
        $this->metadata->primaryKeyAttributes = [];
        $parent = $this->record(['id' => 5]);
        $foreign = $this->record(['name' => 'Private existing record']);
        RelationshipAssignmentModel::$lookup = static fn () => $foreign;

        self::assertNull($parent->findFirstByPrimaryKeys([], RelationshipAssignmentModel::class));
        $created = $parent->getEntityFromData(['name' => 'New'], ['modelClass' => RelationshipAssignmentModel::class]);

        self::assertNotSame($foreign, $created);
        self::assertSame('New', $created->name);
        self::assertSame('Private existing record', $foreign->name);
        self::assertSame([], RelationshipAssignmentModel::$lookups);
    }

    public function testIncompleteCompositeKeyDoesNotSelectAnExistingRecord(): void
    {
        $this->metadata->primaryKeyAttributes = ['tenantId', 'id'];
        $parent = $this->record([]);
        $created = $parent->getEntityFromData(
            ['id' => 7, 'name' => 'New'],
            ['modelClass' => RelationshipAssignmentModel::class, 'fields' => ['tenantId', 'id']]
        );

        self::assertSame('New', $created->name);
        self::assertSame([], RelationshipAssignmentModel::$lookups);
    }

    public function testFalsePrimaryKeyMissKeepsTheNullableLookupContract(): void
    {
        $parent = $this->record([]);
        RelationshipAssignmentModel::$lookup = static fn () => false;

        self::assertNull($parent->findFirstByPrimaryKeys(['id' => 7], RelationshipAssignmentModel::class));
    }

    public function testSparseSingleRelationCreatesAnAllowedChild(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE);
        $parent->assign(
            ['child' => ['name' => 'New child', 'secret' => 'Not allowed']],
            ['child' => ['name', 'parentId']]
        );
        $child = $parent->getDirtyRelatedAlias('child');

        self::assertInstanceOf(RelationshipAssignmentModel::class, $child);
        self::assertSame('New child', $child->name);
        self::assertSame(5, $child->parentId);
        self::assertNull($child->secret);
        self::assertNull($child->id);
    }

    #[DataProvider('allowedAssignmentProvider')]
    public function testExistingAssignmentPoliciesRemainCompatible(int $type, ?int $owner, array $options): void
    {
        $parent = $this->parentWithRelation($type);
        $parent->setRelationshipOptions($options);
        $child = $this->record(['id' => 10, 'parentId' => $owner, 'name' => 'Original', 'secret' => 'Private']);
        RelationshipAssignmentModel::$lookup = static fn () => $child;

        $parent->assign(['child' => ['parentId' => $owner, 'name' => 'Updated', 'secret' => 'Not allowed']], ['child' => ['name']]);

        self::assertSame($child, $parent->getDirtyRelatedAlias('child'));
        self::assertSame('Updated', $child->name);
        self::assertSame('Private', $child->secret);
        self::assertSame($owner, $child->parentId);
    }

    public static function allowedAssignmentProvider(): array
    {
        return [
            'owned single' => [Relation::HAS_ONE, 5, ['enforceDirectOwnership' => true]],
            'owned many' => [Relation::HAS_MANY, 5, ['enforceDirectOwnership' => true]],
            'unowned adoption enabled' => [Relation::HAS_ONE, null, ['enforceDirectOwnership' => true]],
            'legacy reassignment' => [Relation::HAS_ONE, 7, []],
            'belongs-to is not a direct child' => [Relation::BELONGS_TO, 7, ['enforceDirectOwnership' => true]],
        ];
    }

    public function testDeniedRelationAndEmptyAllowlistDoNotTriggerLookupOrAssignment(): void
    {
        $parent = $this->parentWithRelation(Relation::HAS_ONE);
        $parent->name = 'Original';
        $parent->assign(['child' => ['parentId' => 7]], ['name']);
        $parent->assign(['name' => 'Tampered', 'child' => ['parentId' => 7]], []);

        self::assertFalse($parent->hasDirtyRelated());
        self::assertSame('Original', $parent->name);
        self::assertSame([], RelationshipAssignmentModel::$lookups);
    }

    private function parentWithRelation(
        int $type,
        string|array $fields = 'id',
        string|array $referencedFields = 'parentId'
    ): RelationshipAssignmentModel {
        $this->manager->setRelationByAlias(
            RelationshipAssignmentModel::class,
            'child',
            new Relation($type, RelationshipAssignmentModel::class, $fields, $referencedFields, ['alias' => 'child'])
        );
        $parent = $this->record(['id' => 5]);
        $parent->setRelationshipOptions(['enforceDirectOwnership' => true]);
        return $parent;
    }

    private function record(array $attributes): RelationshipAssignmentModel
    {
        $record = new RelationshipAssignmentModel();
        foreach ($attributes as $name => $value) {
            $record->writeAttribute($name, $value);
        }
        return $record;
    }
}
