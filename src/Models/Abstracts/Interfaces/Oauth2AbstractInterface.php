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
 * @property UserAbstractInterface $userentity
 * @property UserAbstractInterface $UserEntity
 * @method UserAbstractInterface getUserEntity(?array $params = null)
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
interface Oauth2AbstractInterface extends ModelInterface
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
     * Returns the value of the field "userId"
     * Column: user_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getUserId(): mixed;
    
    /**
     * Sets the value of the field "userId"
     * Column: user_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @param mixed $userId
     * @return void
     */
    public function setUserId(mixed $userId): void;
    
    /**
     * Returns the value of the field "provider"
     * Column: provider
     * Attributes: NotNull | Size('google','microsoft') | Type(18)
     * @return mixed
     */
    public function getProvider(): mixed;
    
    /**
     * Sets the value of the field "provider"
     * Column: provider
     * Attributes: NotNull | Size('google','microsoft') | Type(18)
     * @param mixed $provider
     * @return void
     */
    public function setProvider(mixed $provider): void;
    
    /**
     * Returns the value of the field "providerUuid"
     * Column: provider_uuid
     * Attributes: NotNull | Size(120) | Type(2)
     * @return mixed
     */
    public function getProviderUuid(): mixed;
    
    /**
     * Sets the value of the field "providerUuid"
     * Column: provider_uuid
     * Attributes: NotNull | Size(120) | Type(2)
     * @param mixed $providerUuid
     * @return void
     */
    public function setProviderUuid(mixed $providerUuid): void;
    
    /**
     * Returns the value of the field "accessToken"
     * Column: access_token
     * Attributes: NotNull | Type(6)
     * @return mixed
     */
    public function getAccessToken(): mixed;
    
    /**
     * Sets the value of the field "accessToken"
     * Column: access_token
     * Attributes: NotNull | Type(6)
     * @param mixed $accessToken
     * @return void
     */
    public function setAccessToken(mixed $accessToken): void;
    
    /**
     * Returns the value of the field "refreshToken"
     * Column: refresh_token
     * Attributes: Type(6)
     * @return mixed
     */
    public function getRefreshToken(): mixed;
    
    /**
     * Sets the value of the field "refreshToken"
     * Column: refresh_token
     * Attributes: Type(6)
     * @param mixed $refreshToken
     * @return void
     */
    public function setRefreshToken(mixed $refreshToken): void;
    
    /**
     * Returns the value of the field "email"
     * Column: email
     * Attributes: Size(320) | Type(2)
     * @return mixed
     */
    public function getEmail(): mixed;
    
    /**
     * Sets the value of the field "email"
     * Column: email
     * Attributes: Size(320) | Type(2)
     * @param mixed $email
     * @return void
     */
    public function setEmail(mixed $email): void;
    
    /**
     * Returns the value of the field "meta"
     * Column: meta
     * Attributes: Type(24)
     * @return mixed
     */
    public function getMeta(): mixed;
    
    /**
     * Sets the value of the field "meta"
     * Column: meta
     * Attributes: Type(24)
     * @param mixed $meta
     * @return void
     */
    public function setMeta(mixed $meta): void;
    
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
