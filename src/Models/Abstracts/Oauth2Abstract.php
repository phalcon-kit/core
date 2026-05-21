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
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\Oauth2AbstractInterface;

/**
 * Class Oauth2Abstract
 *
 * This class defines a Oauth2 abstract model that extends the AbstractModel class and implements the Oauth2AbstractInterface.
 * It provides properties and methods for managing Oauth2 data.
 *
 * @property User $userentity
 * @property User $UserEntity
 * @method User getUserEntity(?array $params = null)
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
abstract class Oauth2Abstract extends AbstractModel implements Oauth2AbstractInterface
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
     * Column: user_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $userId = null;
        
    /**
     * Column: provider
     * Attributes: NotNull | Size('google','microsoft') | Type(18)
     * @var mixed
     */
    public mixed $provider = null;
        
    /**
     * Column: provider_uuid
     * Attributes: NotNull | Size(120) | Type(2)
     * @var mixed
     */
    public mixed $providerUuid = null;
        
    /**
     * Column: access_token
     * Attributes: NotNull | Size(255) | Type(2)
     * @var mixed
     */
    public mixed $accessToken = null;
        
    /**
     * Column: refresh_token
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $refreshToken = 'NULL';
        
    /**
     * Column: email
     * Attributes: Size(320) | Type(2)
     * @var mixed
     */
    public mixed $email = 'NULL';
        
    /**
     * Column: meta
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $meta = 'NULL';
        
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
    public mixed $updatedAt = 'NULL';
        
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
    public mixed $deletedAt = 'NULL';
        
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
     * Returns the value of the field "userId"
     * Column: user_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getUserId(): mixed
    {
        return $this->userId;
    }
    
    /**
     * Sets the value of the field "userId"
     * Column: user_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $userId
     * @return void
     */
    #[\Override]
    public function setUserId(mixed $userId): void
    {
        $this->userId = $userId;
    }
    
    /**
     * Returns the value of the field "provider"
     * Column: provider
     * Attributes: NotNull | Size('google','microsoft') | Type(18)
     * @return mixed
     */
    #[\Override]
    public function getProvider(): mixed
    {
        return $this->provider;
    }
    
    /**
     * Sets the value of the field "provider"
     * Column: provider
     * Attributes: NotNull | Size('google','microsoft') | Type(18)
     * @param mixed $provider
     * @return void
     */
    #[\Override]
    public function setProvider(mixed $provider): void
    {
        $this->provider = $provider;
    }
    
    /**
     * Returns the value of the field "providerUuid"
     * Column: provider_uuid
     * Attributes: NotNull | Size(120) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getProviderUuid(): mixed
    {
        return $this->providerUuid;
    }
    
    /**
     * Sets the value of the field "providerUuid"
     * Column: provider_uuid
     * Attributes: NotNull | Size(120) | Type(2)
     * @param mixed $providerUuid
     * @return void
     */
    #[\Override]
    public function setProviderUuid(mixed $providerUuid): void
    {
        $this->providerUuid = $providerUuid;
    }
    
    /**
     * Returns the value of the field "accessToken"
     * Column: access_token
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getAccessToken(): mixed
    {
        return $this->accessToken;
    }
    
    /**
     * Sets the value of the field "accessToken"
     * Column: access_token
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $accessToken
     * @return void
     */
    #[\Override]
    public function setAccessToken(mixed $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Returns the value of the field "refreshToken"
     * Column: refresh_token
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getRefreshToken(): mixed
    {
        return $this->refreshToken;
    }
    
    /**
     * Sets the value of the field "refreshToken"
     * Column: refresh_token
     * Attributes: Size(255) | Type(2)
     * @param mixed $refreshToken
     * @return void
     */
    #[\Override]
    public function setRefreshToken(mixed $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }
    
    /**
     * Returns the value of the field "email"
     * Column: email
     * Attributes: Size(320) | Type(2)
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
     * Attributes: Size(320) | Type(2)
     * @param mixed $email
     * @return void
     */
    #[\Override]
    public function setEmail(mixed $email): void
    {
        $this->email = $email;
    }
    
    /**
     * Returns the value of the field "meta"
     * Column: meta
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getMeta(): mixed
    {
        return $this->meta;
    }
    
    /**
     * Sets the value of the field "meta"
     * Column: meta
     * Attributes: Type(24)
     * @param mixed $meta
     * @return void
     */
    #[\Override]
    public function setMeta(mixed $meta): void
    {
        $this->meta = $meta;
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
        $this->belongsTo('userId', User::class, 'id', ['alias' => 'UserEntity']);

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
        $this->addUniquenessValidation($validator, ['providerUuid'], true); // provider_uuid_UNIQUE
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addUnsignedIntValidation($validator, 'userId', false);
        $this->addInclusionInValidation($validator, 'provider', ['google','microsoft'], false);
        $this->addStringLengthValidation($validator, 'providerUuid', 0, 120, false);
        $this->addStringLengthValidation($validator, 'accessToken', 0, 255, false);
        $this->addStringLengthValidation($validator, 'refreshToken', 0, 255, true);
        $this->addStringLengthValidation($validator, 'email', 0, 320, true);
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
            'user_id' => 'userId',
            'provider' => 'provider',
            'provider_uuid' => 'providerUuid',
            'access_token' => 'accessToken',
            'refresh_token' => 'refreshToken',
            'email' => 'email',
            'meta' => 'meta',
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
