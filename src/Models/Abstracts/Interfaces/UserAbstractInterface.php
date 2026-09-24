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

namespace PhalconKit\Models\Abstracts\Interfaces;

use Phalcon\Db\RawValue;
use PhalconKit\Mvc\ModelInterface;

/**
 * @property Oauth2AbstractInterface[] $oauth2list
 * @property Oauth2AbstractInterface[] $Oauth2List
 * @method Oauth2AbstractInterface[] getOauth2List(?array $params = null)
 *
 * @property ProfileAbstractInterface[] $profilelist
 * @property ProfileAbstractInterface[] $ProfileList
 * @method ProfileAbstractInterface[] getProfileList(?array $params = null)
 *
 * @property SessionAbstractInterface[] $sessionlist
 * @property SessionAbstractInterface[] $SessionList
 * @method SessionAbstractInterface[] getSessionList(?array $params = null)
 *
 * @property UserFeatureAbstractInterface[] $userfeaturelist
 * @property UserFeatureAbstractInterface[] $UserFeatureList
 * @method UserFeatureAbstractInterface[] getUserFeatureList(?array $params = null)
 *
 * @property FeatureAbstractInterface[] $featurelist
 * @property FeatureAbstractInterface[] $FeatureList
 * @method FeatureAbstractInterface[] getFeatureList(?array $params = null)
 *
 * @property UserGroupAbstractInterface[] $usergrouplist
 * @property UserGroupAbstractInterface[] $UserGroupList
 * @method UserGroupAbstractInterface[] getUserGroupList(?array $params = null)
 *
 * @property GroupAbstractInterface[] $grouplist
 * @property GroupAbstractInterface[] $GroupList
 * @method GroupAbstractInterface[] getGroupList(?array $params = null)
 *
 * @property UserRoleAbstractInterface[] $userrolelist
 * @property UserRoleAbstractInterface[] $UserRoleList
 * @method UserRoleAbstractInterface[] getUserRoleList(?array $params = null)
 *
 * @property RoleAbstractInterface[] $rolelist
 * @property RoleAbstractInterface[] $RoleList
 * @method RoleAbstractInterface[] getRoleList(?array $params = null)
 *
 * @property UserTypeAbstractInterface[] $usertypelist
 * @property UserTypeAbstractInterface[] $UserTypeList
 * @method UserTypeAbstractInterface[] getUserTypeList(?array $params = null)
 *
 * @property TypeAbstractInterface[] $typelist
 * @property TypeAbstractInterface[] $TypeList
 * @method TypeAbstractInterface[] getTypeList(?array $params = null)
 *
 * @property UserAbstractInterface $createdbyentity
 * @property UserAbstractInterface $CreatedByEntity
 * @method UserAbstractInterface getCreatedByEntity(?array $params = null)
 *
 * @property UserAbstractInterface $updatedbyentity
 * @property UserAbstractInterface $UpdatedByEntity
 * @method UserAbstractInterface getUpdatedByEntity(?array $params = null)
 *
 * @property UserAbstractInterface $deletedbyentity
 * @property UserAbstractInterface $DeletedByEntity
 * @method UserAbstractInterface getDeletedByEntity(?array $params = null)
 */
interface UserAbstractInterface extends ModelInterface
{
    /**
     * Returns the value of the field "id"
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Type(14)
     * @return mixed
     */
    public function getId(): mixed;
    
    /**
     * Sets the value of the field "id"
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Type(14)
     * @param mixed $id
     * @return void
     */
    public function setId(mixed $id): void;
    
    /**
     * Returns the value of the field "uuid"
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @return mixed
     */
    public function getUuid(): mixed;
    
    /**
     * Sets the value of the field "uuid"
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @param mixed $uuid
     * @return void
     */
    public function setUuid(mixed $uuid): void;
    
    /**
     * Returns the value of the field "email"
     * Column: email
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    public function getEmail(): mixed;
    
    /**
     * Sets the value of the field "email"
     * Column: email
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $email
     * @return void
     */
    public function setEmail(mixed $email): void;
    
    /**
     * Returns the value of the field "password"
     * Column: password
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    public function getPassword(): mixed;
    
    /**
     * Sets the value of the field "password"
     * Column: password
     * Attributes: Size(255) | Type(2)
     * @param mixed $password
     * @return void
     */
    public function setPassword(mixed $password): void;
    
    /**
     * Returns the value of the field "resetToken"
     * Column: reset_token
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    public function getResetToken(): mixed;
    
    /**
     * Sets the value of the field "resetToken"
     * Column: reset_token
     * Attributes: Size(255) | Type(2)
     * @param mixed $resetToken
     * @return void
     */
    public function setResetToken(mixed $resetToken): void;
    
    /**
     * Returns the value of the field "deleted"
     * Column: deleted
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @return mixed
     */
    public function getDeleted(): mixed;
    
    /**
     * Sets the value of the field "deleted"
     * Column: deleted
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @param mixed $deleted
     * @return void
     */
    public function setDeleted(mixed $deleted): void;
    
    /**
     * Returns the value of the field "createdAt"
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @return mixed
     */
    public function getCreatedAt(): mixed;
    
    /**
     * Sets the value of the field "createdAt"
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @param mixed $createdAt
     * @return void
     */
    public function setCreatedAt(mixed $createdAt): void;
    
    /**
     * Returns the value of the field "createdBy"
     * Column: created_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getCreatedBy(): mixed;
    
    /**
     * Sets the value of the field "createdBy"
     * Column: created_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $createdBy
     * @return void
     */
    public function setCreatedBy(mixed $createdBy): void;
    
    /**
     * Returns the value of the field "updatedAt"
     * Column: updated_at
     * Attributes: Type(4)
     * @return mixed
     */
    public function getUpdatedAt(): mixed;
    
    /**
     * Sets the value of the field "updatedAt"
     * Column: updated_at
     * Attributes: Type(4)
     * @param mixed $updatedAt
     * @return void
     */
    public function setUpdatedAt(mixed $updatedAt): void;
    
    /**
     * Returns the value of the field "updatedBy"
     * Column: updated_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getUpdatedBy(): mixed;
    
    /**
     * Sets the value of the field "updatedBy"
     * Column: updated_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $updatedBy
     * @return void
     */
    public function setUpdatedBy(mixed $updatedBy): void;
    
    /**
     * Returns the value of the field "deletedAt"
     * Column: deleted_at
     * Attributes: Type(4)
     * @return mixed
     */
    public function getDeletedAt(): mixed;
    
    /**
     * Sets the value of the field "deletedAt"
     * Column: deleted_at
     * Attributes: Type(4)
     * @param mixed $deletedAt
     * @return void
     */
    public function setDeletedAt(mixed $deletedAt): void;
    
    /**
     * Returns the value of the field "deletedBy"
     * Column: deleted_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getDeletedBy(): mixed;
    
    /**
     * Sets the value of the field "deletedBy"
     * Column: deleted_by
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $deletedBy
     * @return void
     */
    public function setDeletedBy(mixed $deletedBy): void;
}
