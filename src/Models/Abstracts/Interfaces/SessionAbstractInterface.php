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
 * @property UserAbstractInterface $asuserentity
 * @property UserAbstractInterface $AsUserEntity
 * @method UserAbstractInterface getAsUserEntity(?array $params = null)
 */
interface SessionAbstractInterface extends ModelInterface
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
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getUserId(): mixed;
    
    /**
     * Sets the value of the field "userId"
     * Column: user_id
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $userId
     * @return void
     */
    public function setUserId(mixed $userId): void;
    
    /**
     * Returns the value of the field "asUserId"
     * Column: as_user_id
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getAsUserId(): mixed;
    
    /**
     * Sets the value of the field "asUserId"
     * Column: as_user_id
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $asUserId
     * @return void
     */
    public function setAsUserId(mixed $asUserId): void;
    
    /**
     * Returns the value of the field "token"
     * Column: token
     * Attributes: NotNull | Size(128) | Type(2)
     * @return mixed
     */
    public function getToken(): mixed;
    
    /**
     * Sets the value of the field "token"
     * Column: token
     * Attributes: NotNull | Size(128) | Type(2)
     * @param mixed $token
     * @return void
     */
    public function setToken(mixed $token): void;
    
    /**
     * Returns the value of the field "jwt"
     * Column: jwt
     * Attributes: Type(6)
     * @return mixed
     */
    public function getJwt(): mixed;
    
    /**
     * Sets the value of the field "jwt"
     * Column: jwt
     * Attributes: Type(6)
     * @param mixed $jwt
     * @return void
     */
    public function setJwt(mixed $jwt): void;
    
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
     * Returns the value of the field "expiresAt"
     * Column: expires_at
     * Attributes: NotNull | Type(4)
     * @return mixed
     */
    public function getExpiresAt(): mixed;
    
    /**
     * Sets the value of the field "expiresAt"
     * Column: expires_at
     * Attributes: NotNull | Type(4)
     * @param mixed $expiresAt
     * @return void
     */
    public function setExpiresAt(mixed $expiresAt): void;
    
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
}
