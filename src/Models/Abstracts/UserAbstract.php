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
use PhalconKit\Models\Oauth2;
use PhalconKit\Models\Profile;
use PhalconKit\Models\Session;
use PhalconKit\Models\UserFeature;
use PhalconKit\Models\Feature;
use PhalconKit\Models\UserGroup;
use PhalconKit\Models\Group;
use PhalconKit\Models\UserRole;
use PhalconKit\Models\Role;
use PhalconKit\Models\UserType;
use PhalconKit\Models\Type;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\UserAbstractInterface;

/**
 * Class UserAbstract
 *
 * This class defines a User abstract model that extends the AbstractModel class and implements the UserAbstractInterface.
 * It provides properties and methods for managing User data.
 *
 * @property Oauth2[] $oauth2list
 * @property Oauth2[] $Oauth2List
 * @method Oauth2[] getOauth2List(?array $params = null)
 *
 * @property Profile[] $profilelist
 * @property Profile[] $ProfileList
 * @method Profile[] getProfileList(?array $params = null)
 *
 * @property Session[] $sessionlist
 * @property Session[] $SessionList
 * @method Session[] getSessionList(?array $params = null)
 *
 * @property UserFeature[] $userfeaturelist
 * @property UserFeature[] $UserFeatureList
 * @method UserFeature[] getUserFeatureList(?array $params = null)
 *
 * @property Feature[] $featurelist
 * @property Feature[] $FeatureList
 * @method Feature[] getFeatureList(?array $params = null)
 *
 * @property UserGroup[] $usergrouplist
 * @property UserGroup[] $UserGroupList
 * @method UserGroup[] getUserGroupList(?array $params = null)
 *
 * @property Group[] $grouplist
 * @property Group[] $GroupList
 * @method Group[] getGroupList(?array $params = null)
 *
 * @property UserRole[] $userrolelist
 * @property UserRole[] $UserRoleList
 * @method UserRole[] getUserRoleList(?array $params = null)
 *
 * @property Role[] $rolelist
 * @property Role[] $RoleList
 * @method Role[] getRoleList(?array $params = null)
 *
 * @property UserType[] $usertypelist
 * @property UserType[] $UserTypeList
 * @method UserType[] getUserTypeList(?array $params = null)
 *
 * @property Type[] $typelist
 * @property Type[] $TypeList
 * @method Type[] getTypeList(?array $params = null)
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
abstract class UserAbstract extends AbstractModel implements UserAbstractInterface
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
     * Column: email
     * Attributes: NotNull | Size(255) | Type(2)
     * @var mixed
     */
    public mixed $email = null;
        
    /**
     * Column: password
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $password = null;
        
    /**
     * Column: reset_token
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $resetToken = null;
        
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
    public mixed $createdBy = 0;
        
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
    public mixed $updatedBy = 0;
        
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
    public mixed $deletedBy = 0;
    
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
     * Returns the value of the field "email"
     * Column: email
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getEmail(): mixed
    {
        return $this->email;
    }
    
    /**
     * Sets the value of the field "email"
     * Column: email
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $email
     * @return void
     */
    #[\Override]
    public function setEmail(mixed $email): void
    {
        $this->email = $email;
    }
    
    /**
     * Returns the value of the field "password"
     * Column: password
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getPassword(): mixed
    {
        return $this->password;
    }
    
    /**
     * Sets the value of the field "password"
     * Column: password
     * Attributes: Size(255) | Type(2)
     * @param mixed $password
     * @return void
     */
    #[\Override]
    public function setPassword(mixed $password): void
    {
        $this->password = $password;
    }
    
    /**
     * Returns the value of the field "resetToken"
     * Column: reset_token
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getResetToken(): mixed
    {
        return $this->resetToken;
    }
    
    /**
     * Sets the value of the field "resetToken"
     * Column: reset_token
     * Attributes: Size(255) | Type(2)
     * @param mixed $resetToken
     * @return void
     */
    #[\Override]
    public function setResetToken(mixed $resetToken): void
    {
        $this->resetToken = $resetToken;
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
        $this->hasMany('id', Oauth2::class, 'userId', ['alias' => 'Oauth2List']);

        $this->hasMany('id', Profile::class, 'userId', ['alias' => 'ProfileList']);

        $this->hasMany('id', Session::class, 'userId', ['alias' => 'SessionList']);

        $this->hasMany('id', UserFeature::class, 'userId', ['alias' => 'UserFeatureList']);

        $this->hasManyToMany(
            'id',
            UserFeature::class,
            'userId',
            'featureId',
            Feature::class,
            'id',
            ['alias' => 'FeatureList']
        );

        $this->hasMany('id', UserGroup::class, 'userId', ['alias' => 'UserGroupList']);

        $this->hasManyToMany(
            'id',
            UserGroup::class,
            'userId',
            'groupId',
            Group::class,
            'id',
            ['alias' => 'GroupList']
        );

        $this->hasMany('id', UserRole::class, 'userId', ['alias' => 'UserRoleList']);

        $this->hasManyToMany(
            'id',
            UserRole::class,
            'userId',
            'roleId',
            Role::class,
            'id',
            ['alias' => 'RoleList']
        );

        $this->hasMany('id', UserType::class, 'userId', ['alias' => 'UserTypeList']);

        $this->hasManyToMany(
            'id',
            UserType::class,
            'userId',
            'typeId',
            Type::class,
            'id',
            ['alias' => 'TypeList']
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
        $this->addUniquenessValidation($validator, ['email'], true); // email_UNIQUE
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addStringLengthValidation($validator, 'email', 0, 255, false);
        $this->addStringLengthValidation($validator, 'password', 0, 255, true);
        $this->addStringLengthValidation($validator, 'resetToken', 0, 255, true);
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
            'email' => 'email',
            'password' => 'password',
            'reset_token' => 'resetToken',
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
