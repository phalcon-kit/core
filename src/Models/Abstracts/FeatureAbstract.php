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

namespace PhalconKit\Models\Abstracts;

use Phalcon\Db\RawValue;
use PhalconKit\Filter\Validation;
use PhalconKit\Models\AbstractModel;
use PhalconKit\Models\GroupFeature;
use PhalconKit\Models\Group;
use PhalconKit\Models\RoleFeature;
use PhalconKit\Models\Role;
use PhalconKit\Models\UserFeature;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\FeatureAbstractInterface;

/**
 * Class FeatureAbstract
 *
 * This class defines a Feature abstract model that extends the AbstractModel class and implements the FeatureAbstractInterface.
 * It provides properties and methods for managing Feature data.
 *
 * @property GroupFeature[] $groupfeaturelist
 * @property GroupFeature[] $GroupFeatureList
 * @method GroupFeature[] getGroupFeatureList(?array $params = null)
 *
 * @property Group[] $grouplist
 * @property Group[] $GroupList
 * @method Group[] getGroupList(?array $params = null)
 *
 * @property RoleFeature[] $rolefeaturelist
 * @property RoleFeature[] $RoleFeatureList
 * @method RoleFeature[] getRoleFeatureList(?array $params = null)
 *
 * @property Role[] $rolelist
 * @property Role[] $RoleList
 * @method Role[] getRoleList(?array $params = null)
 *
 * @property UserFeature[] $userfeaturelist
 * @property UserFeature[] $UserFeatureList
 * @method UserFeature[] getUserFeatureList(?array $params = null)
 *
 * @property User[] $userlist
 * @property User[] $UserList
 * @method User[] getUserList(?array $params = null)
 *
 * @property User $createdbyentity
 * @property User $CreatedByEntity
 * @method User getCreatedByEntity(?array $params = null)
 *
 * @property User $updatedbyentity
 * @property User $UpdatedByEntity
 * @method User getUpdatedByEntity(?array $params = null)
 *
 * @property User $deletedbyentity
 * @property User $DeletedByEntity
 * @method User getDeletedByEntity(?array $params = null)
 */
abstract class FeatureAbstract extends AbstractModel implements FeatureAbstractInterface
{
    /**
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $id = null;
        
    /**
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @var mixed
     */
    public mixed $uuid = null;
        
    /**
     * Column: key
     * Attributes: NotNull | Size(50) | Type(2)
     * @var mixed
     */
    public mixed $key = null;
        
    /**
     * Column: label
     * Attributes: NotNull | Size(100) | Type(2)
     * @var mixed
     */
    public mixed $label = null;
        
    /**
     * Column: position
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @var mixed
     */
    public mixed $position = 0;
        
    /**
     * Column: deleted
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @var mixed
     */
    public mixed $deleted = 0;
        
    /**
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @var mixed
     */
    public mixed $createdAt = 'current_timestamp()';
        
    /**
     * Column: created_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $createdBy = null;
        
    /**
     * Column: updated_at
     * Attributes: Type(4)
     * @var mixed
     */
    public mixed $updatedAt = null;
        
    /**
     * Column: updated_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $updatedBy = null;
        
    /**
     * Column: deleted_at
     * Attributes: Type(4)
     * @var mixed
     */
    public mixed $deletedAt = null;
        
    /**
     * Column: deleted_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $deletedBy = null;
    
    /**
     * Returns the value of the field "id"
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getId(): mixed
    {
        return $this->id;
    }
    
    /**
     * Sets the value of the field "id"
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Size(1) | Type(14)
     * @param mixed $id
     * @return void
     */
    #[\Override]
    public function setId(mixed $id): void
    {
        $this->id = $id;
    }
    
    /**
     * Returns the value of the field "uuid"
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @return mixed
     */
    #[\Override]
    public function getUuid(): mixed
    {
        return $this->uuid;
    }
    
    /**
     * Sets the value of the field "uuid"
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @param mixed $uuid
     * @return void
     */
    #[\Override]
    public function setUuid(mixed $uuid): void
    {
        $this->uuid = $uuid;
    }
    
    /**
     * Returns the value of the field "key"
     * Column: key
     * Attributes: NotNull | Size(50) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getKey(): mixed
    {
        return $this->key;
    }
    
    /**
     * Sets the value of the field "key"
     * Column: key
     * Attributes: NotNull | Size(50) | Type(2)
     * @param mixed $key
     * @return void
     */
    #[\Override]
    public function setKey(mixed $key): void
    {
        $this->key = $key;
    }
    
    /**
     * Returns the value of the field "label"
     * Column: label
     * Attributes: NotNull | Size(100) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getLabel(): mixed
    {
        return $this->label;
    }
    
    /**
     * Sets the value of the field "label"
     * Column: label
     * Attributes: NotNull | Size(100) | Type(2)
     * @param mixed $label
     * @return void
     */
    #[\Override]
    public function setLabel(mixed $label): void
    {
        $this->label = $label;
    }
    
    /**
     * Returns the value of the field "position"
     * Column: position
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @return mixed
     */
    #[\Override]
    public function getPosition(): mixed
    {
        return $this->position;
    }
    
    /**
     * Sets the value of the field "position"
     * Column: position
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @param mixed $position
     * @return void
     */
    #[\Override]
    public function setPosition(mixed $position): void
    {
        $this->position = $position;
    }
    
    /**
     * Returns the value of the field "deleted"
     * Column: deleted
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @return mixed
     */
    #[\Override]
    public function getDeleted(): mixed
    {
        return $this->deleted;
    }
    
    /**
     * Sets the value of the field "deleted"
     * Column: deleted
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @param mixed $deleted
     * @return void
     */
    #[\Override]
    public function setDeleted(mixed $deleted): void
    {
        $this->deleted = $deleted;
    }
    
    /**
     * Returns the value of the field "createdAt"
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @return mixed
     */
    #[\Override]
    public function getCreatedAt(): mixed
    {
        return $this->createdAt;
    }
    
    /**
     * Sets the value of the field "createdAt"
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @param mixed $createdAt
     * @return void
     */
    #[\Override]
    public function setCreatedAt(mixed $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    /**
     * Returns the value of the field "createdBy"
     * Column: created_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getCreatedBy(): mixed
    {
        return $this->createdBy;
    }
    
    /**
     * Sets the value of the field "createdBy"
     * Column: created_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $createdBy
     * @return void
     */
    #[\Override]
    public function setCreatedBy(mixed $createdBy): void
    {
        $this->createdBy = $createdBy;
    }
    
    /**
     * Returns the value of the field "updatedAt"
     * Column: updated_at
     * Attributes: Type(4)
     * @return mixed
     */
    #[\Override]
    public function getUpdatedAt(): mixed
    {
        return $this->updatedAt;
    }
    
    /**
     * Sets the value of the field "updatedAt"
     * Column: updated_at
     * Attributes: Type(4)
     * @param mixed $updatedAt
     * @return void
     */
    #[\Override]
    public function setUpdatedAt(mixed $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
    
    /**
     * Returns the value of the field "updatedBy"
     * Column: updated_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getUpdatedBy(): mixed
    {
        return $this->updatedBy;
    }
    
    /**
     * Sets the value of the field "updatedBy"
     * Column: updated_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $updatedBy
     * @return void
     */
    #[\Override]
    public function setUpdatedBy(mixed $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }
    
    /**
     * Returns the value of the field "deletedAt"
     * Column: deleted_at
     * Attributes: Type(4)
     * @return mixed
     */
    #[\Override]
    public function getDeletedAt(): mixed
    {
        return $this->deletedAt;
    }
    
    /**
     * Sets the value of the field "deletedAt"
     * Column: deleted_at
     * Attributes: Type(4)
     * @param mixed $deletedAt
     * @return void
     */
    #[\Override]
    public function setDeletedAt(mixed $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }
    
    /**
     * Returns the value of the field "deletedBy"
     * Column: deleted_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getDeletedBy(): mixed
    {
        return $this->deletedBy;
    }
    
    /**
     * Sets the value of the field "deletedBy"
     * Column: deleted_by
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $deletedBy
     * @return void
     */
    #[\Override]
    public function setDeletedBy(mixed $deletedBy): void
    {
        $this->deletedBy = $deletedBy;
    }

    /**
     * Adds the default relationships to the model.
     * @return void
     */
    public function addDefaultRelationships(): void
    {
        $this->hasMany('id', GroupFeature::class, 'featureId', ['alias' => 'GroupFeatureList']);

        $this->hasManyToMany(
            'id',
            GroupFeature::class,
            'featureId',
            'groupId',
            Group::class,
            'id',
            ['alias' => 'GroupList']
        );

        $this->hasMany('id', RoleFeature::class, 'featureId', ['alias' => 'RoleFeatureList']);

        $this->hasManyToMany(
            'id',
            RoleFeature::class,
            'featureId',
            'roleId',
            Role::class,
            'id',
            ['alias' => 'RoleList']
        );

        $this->hasMany('id', UserFeature::class, 'featureId', ['alias' => 'UserFeatureList']);

        $this->hasManyToMany(
            'id',
            UserFeature::class,
            'featureId',
            'userId',
            User::class,
            'id',
            ['alias' => 'UserList']
        );

        $this->belongsTo('createdBy', User::class, 'id', ['alias' => 'CreatedByEntity']);

        $this->belongsTo('updatedBy', User::class, 'id', ['alias' => 'UpdatedByEntity']);

        $this->belongsTo('deletedBy', User::class, 'id', ['alias' => 'DeletedByEntity']);
    }
    
    /**
     * Adds the default validations to the model.
     * @param Validation|null $validator
     * @return Validation
     */
    public function addDefaultValidations(?Validation $validator = null): Validation
    {
        $validator ??= new Validation();
    
        $this->addUniquenessValidation($validator, ['id'], true); // PRIMARY
        $this->addUniquenessValidation($validator, ['uuid'], true); // uuid_UNIQUE
        $this->addUniquenessValidation($validator, ['key'], true); // key_UNIQUE
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addStringLengthValidation($validator, 'key', 0, 50, false);
        $this->addStringLengthValidation($validator, 'label', 0, 100, false);
        $this->addUnsignedIntValidation($validator, 'position', false);
        $this->addUnsignedIntValidation($validator, 'deleted', false);
        $this->addDateTimeValidation($validator, 'createdAt', false);
        $this->addUnsignedIntValidation($validator, 'createdBy', true);
        $this->addDateTimeValidation($validator, 'updatedAt', true);
        $this->addUnsignedIntValidation($validator, 'updatedBy', true);
        $this->addDateTimeValidation($validator, 'deletedAt', true);
        $this->addUnsignedIntValidation($validator, 'deletedBy', true);
        
        return $validator;
    }

        
    /**
     * Returns an array that maps the column names of the database
     * table to the corresponding property names of the model.
     *
     * @returns array The array mapping the column names to the property names
     */
    public function columnMap(): array
    {
        return [
            'id' => 'id',
            'uuid' => 'uuid',
            'key' => 'key',
            'label' => 'label',
            'position' => 'position',
            'deleted' => 'deleted',
            'created_at' => 'createdAt',
            'created_by' => 'createdBy',
            'updated_at' => 'updatedAt',
            'updated_by' => 'updatedBy',
            'deleted_at' => 'deletedAt',
            'deleted_by' => 'deletedBy',
        ];
    }
}
