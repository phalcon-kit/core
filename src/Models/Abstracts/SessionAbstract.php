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
use PhalconKit\Models\Abstracts\Interfaces\SessionAbstractInterface;

/**
 * Class SessionAbstract
 *
 * This class defines a Session abstract model that extends the AbstractModel class and implements the SessionAbstractInterface.
 * It provides properties and methods for managing Session data.
 *
 * @property User $userentity
 * @property User $UserEntity
 * @method User getUserEntity(?array $params = null)
 *
 * @property User $asuserentity
 * @property User $AsUserEntity
 * @method User getAsUserEntity(?array $params = null)
 */
abstract class SessionAbstract extends AbstractModel implements SessionAbstractInterface
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
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $userId = 0;
        
    /**
     * Column: as_user_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $asUserId = 0;
        
    /**
     * Column: token
     * Attributes: NotNull | Size(128) | Type(2)
     * @var mixed
     */
    public mixed $token = null;
        
    /**
     * Column: jwt
     * Attributes: Type(6)
     * @var mixed
     */
    public mixed $jwt = 'NULL';
        
    /**
     * Column: meta
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $meta = 'NULL';
        
    /**
     * Column: expires_at
     * Attributes: NotNull | Type(4)
     * @var mixed
     */
    public mixed $expiresAt = null;
        
    /**
     * Column: created_at
     * Attributes: NotNull | Type(4)
     * @var mixed
     */
    public mixed $createdAt = 'current_timestamp()';
    
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
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
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
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $userId
     * @return void
     */
    #[\Override]
    public function setUserId(mixed $userId): void
    {
        $this->userId = $userId;
    }
    
    /**
     * Returns the value of the field "asUserId"
     * Column: as_user_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getAsUserId(): mixed
    {
        return $this->asUserId;
    }
    
    /**
     * Sets the value of the field "asUserId"
     * Column: as_user_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $asUserId
     * @return void
     */
    #[\Override]
    public function setAsUserId(mixed $asUserId): void
    {
        $this->asUserId = $asUserId;
    }
    
    /**
     * Returns the value of the field "token"
     * Column: token
     * Attributes: NotNull | Size(128) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getToken(): mixed
    {
        return $this->token;
    }
    
    /**
     * Sets the value of the field "token"
     * Column: token
     * Attributes: NotNull | Size(128) | Type(2)
     * @param mixed $token
     * @return void
     */
    #[\Override]
    public function setToken(mixed $token): void
    {
        $this->token = $token;
    }
    
    /**
     * Returns the value of the field "jwt"
     * Column: jwt
     * Attributes: Type(6)
     * @return mixed
     */
    #[\Override]
    public function getJwt(): mixed
    {
        return $this->jwt;
    }
    
    /**
     * Sets the value of the field "jwt"
     * Column: jwt
     * Attributes: Type(6)
     * @param mixed $jwt
     * @return void
     */
    #[\Override]
    public function setJwt(mixed $jwt): void
    {
        $this->jwt = $jwt;
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
     * Returns the value of the field "expiresAt"
     * Column: expires_at
     * Attributes: NotNull | Type(4)
     * @return mixed
     */
    #[\Override]
    public function getExpiresAt(): mixed
    {
        return $this->expiresAt;
    }
    
    /**
     * Sets the value of the field "expiresAt"
     * Column: expires_at
     * Attributes: NotNull | Type(4)
     * @param mixed $expiresAt
     * @return void
     */
    #[\Override]
    public function setExpiresAt(mixed $expiresAt): void
    {
        $this->expiresAt = $expiresAt;
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
     * Adds the default relationships to the model.
     * @return void
     */
    public function addDefaultRelationships(): void
    {
        $this->belongsTo('userId', User::class, 'id', ['alias' => 'UserEntity']);

        $this->belongsTo('asUserId', User::class, 'id', ['alias' => 'AsUserEntity']);
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
        $this->addUniquenessValidation($validator, ['token'], true); // token_UNIQUE
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addUnsignedIntValidation($validator, 'userId', true);
        $this->addUnsignedIntValidation($validator, 'asUserId', true);
        $this->addStringLengthValidation($validator, 'token', 0, 128, false);
        $this->addDateTimeValidation($validator, 'expiresAt', false);
        $this->addDateTimeValidation($validator, 'createdAt', false);
        
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
            'as_user_id' => 'asUserId',
            'token' => 'token',
            'jwt' => 'jwt',
            'meta' => 'meta',
            'expires_at' => 'expiresAt',
            'created_at' => 'createdAt',
        ];
    }
}
