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

namespace PhalconKit\Tests\Unit\Mvc\Model\Fixtures;

use Phalcon\Db\Adapter\AdapterInterface;
use Phalcon\Messages\MessageInterface;
use Phalcon\Mvc\Model\BehaviorInterface;
use Phalcon\Mvc\Model\MetaDataInterface;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;
use Phalcon\Support\Collection\CollectionInterface;
use PhalconKit\Mvc\Model;

class ModelBehaviorDouble extends Model
{
    public static ModelInterface|Row|false|null $findFirstResult = null;

    public mixed $id = null;
    public mixed $parentId = null;
    public mixed $child = null;
    public mixed $camelChild = null;
    public mixed $name = null;
    public mixed $nameEn = null;
    public mixed $position = null;
    public mixed $deleted = 0;
    public mixed $uuid = null;
    public mixed $slug = null;
    public mixed $userId = null;
    public mixed $createdBy = null;
    public mixed $createdAs = null;
    public mixed $createdAt = null;
    public mixed $updatedBy = null;
    public mixed $updatedAs = null;
    public mixed $updatedAt = null;
    public mixed $deletedBy = null;
    public mixed $deletedAs = null;
    public mixed $deletedAt = null;
    public mixed $restoredBy = null;
    public mixed $restoredAs = null;
    public mixed $restoredAt = null;
    public mixed $number = null;
    public mixed $big = null;
    public mixed $string = null;
    public mixed $status = null;
    public mixed $email = null;
    public mixed $date = null;
    public mixed $datetime = null;
    public mixed $json = null;
    public mixed $color = null;

    public static mixed $staticchild = null;

    public array $attributes = [];
    public array $addedBehaviors = [];
    public array $messages = [];
    public array $firedEvents = [];
    public array $cancelEvents = [];
    public array $snapshotData = [];
    public array $oldSnapshotData = [];
    public array $changedFields = [];
    public array $primaryKeysValues = ['id' => 1];
    public bool $hasSnapshotData = false;
    public bool $hasUpdated = false;
    public bool $hasChanged = false;
    public bool $saveResult = true;
    public bool $doSaveResult = true;
    public bool $keepSnapshotsValue = false;
    public bool $skipRestore = false;
    public string $source = 'model_behavior_double';
    public ?AdapterInterface $writeConnection = null;
    public ?FakeModelsManager $fakeModelsManager = null;
    public ?MetaDataInterface $fakeModelsMetaData = null;

    #[\Override]
    public function initialize(): void
    {
        $this->setSource($this->source);
    }

    #[\Override]
    public static function findFirst(mixed $parameters = null): ModelInterface|Row|false|null
    {
        return self::$findFirstResult;
    }

    #[\Override]
    public function readAttribute(string $attribute)
    {
        return property_exists($this, $attribute) ? $this->{$attribute} : ($this->attributes[$attribute] ?? null);
    }

    #[\Override]
    public function writeAttribute(string $attribute, mixed $value): void
    {
        if (property_exists($this, $attribute)) {
            $this->{$attribute} = $value;
            return;
        }

        $this->attributes[$attribute] = $value;
    }

    #[\Override]
    public function assign(array $data, $whiteList = null, $dataColumnMap = null): ModelInterface
    {
        foreach ($data as $field => $value) {
            if ($whiteList !== null && !in_array($field, $whiteList, true) && !array_key_exists($field, $whiteList)) {
                continue;
            }

            $this->writeAttribute((string)$field, $value);
        }

        return $this;
    }

    #[\Override]
    public function toArray($columns = null, $useGetter = true): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'position' => $this->position,
            'deleted' => $this->deleted,
            'uuid' => $this->uuid,
            'slug' => $this->slug,
            'createdBy' => $this->createdBy,
            'updatedBy' => $this->updatedBy,
            'deletedBy' => $this->deletedBy,
            'restoredBy' => $this->restoredBy,
        ];

        $data = array_merge($data, $this->attributes);

        if ($columns === null) {
            return $data;
        }

        return array_intersect_key($data, array_flip($columns));
    }

    #[\Override]
    public function appendMessage(MessageInterface $message): ModelInterface
    {
        $this->messages[] = $message;
        return $this;
    }

    #[\Override]
    public function getMessages($filter = null): array
    {
        return $this->messages;
    }

    #[\Override]
    public function fireEventCancel(string $eventName): bool
    {
        $this->firedEvents[] = $eventName;
        if ($eventName === 'beforeRestore' && $this->skipRestore) {
            $this->skipped = true;
        }

        return !in_array($eventName, $this->cancelEvents, true);
    }

    #[\Override]
    public function fireEvent(string $eventName): bool
    {
        $this->firedEvents[] = $eventName;
        return true;
    }

    #[\Override]
    public function save(): bool
    {
        return $this->saveResult;
    }

    #[\Override]
    public function doSave(CollectionInterface $visited): bool
    {
        return $this->doSaveResult;
    }

    #[\Override]
    public function keepSnapshots(bool $keepSnapshot): void
    {
        $this->keepSnapshotsValue = $keepSnapshot;
    }

    #[\Override]
    public function setSnapshotData(array $data, $columnMap = null): void
    {
        $this->snapshotData = $data;
        $this->hasSnapshotData = true;
    }

    #[\Override]
    public function setOldSnapshotData(array $data, $columnMap = null): void
    {
        $this->oldSnapshotData = $data;
    }

    #[\Override]
    public function getSnapshotData(): array
    {
        return $this->snapshotData;
    }

    #[\Override]
    public function getOldSnapshotData(): array
    {
        return $this->oldSnapshotData;
    }

    #[\Override]
    public function hasSnapshotData(): bool
    {
        return $this->hasSnapshotData;
    }

    #[\Override]
    public function hasUpdated($fieldName = null, bool $allFields = false): bool
    {
        return $fieldName === null ? $this->hasUpdated : in_array($fieldName, $this->changedFields, true);
    }

    #[\Override]
    public function hasChanged($fieldName = null, bool $allFields = false): bool
    {
        return $fieldName === null ? $this->hasChanged : in_array($fieldName, $this->changedFields, true);
    }

    #[\Override]
    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    #[\Override]
    public function getPrimaryKeysValues(): array
    {
        return $this->primaryKeysValues;
    }

    #[\Override]
    public function getModelsManager(): \Phalcon\Mvc\Model\ManagerInterface
    {
        return $this->fakeModelsManager ?? parent::getModelsManager();
    }

    #[\Override]
    public function getModelsMetaData(): MetaDataInterface
    {
        return $this->fakeModelsMetaData ?? parent::getModelsMetaData();
    }

    #[\Override]
    public function addBehavior(BehaviorInterface $behavior): void
    {
        $this->addedBehaviors[] = $behavior;
    }

    public function callPreSaveRelatedRecords(
        AdapterInterface $connection,
        array $related,
        CollectionInterface $visited
    ): bool {
        return $this->preSaveRelatedRecords($connection, $related, $visited);
    }

    public function callPostSaveRelatedRecords(
        AdapterInterface $connection,
        array $related,
        CollectionInterface $visited
    ): bool {
        return $this->postSaveRelatedRecords($connection, $related, $visited);
    }


    public function publicGetAllowEmptyOption(bool $allowEmpty = true): bool|array
    {
        return $this->getAllowEmptyOption($allowEmpty);
    }

    public function publicShouldSkipOptionalValidation(array|string $field, bool $allowEmpty): bool
    {
        return $this->shouldSkipOptionalValidation($field, $allowEmpty);
    }

    public function publicNormalizeNullableNullStrings(): void
    {
        $this->normalizeNullableNullStrings();
    }
}
