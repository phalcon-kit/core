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
 * @property UserAbstractInterface $createdbyentity
 * @property UserAbstractInterface $CreatedByEntity
 * @method UserAbstractInterface getCreatedByEntity(?array $params = null)
 */
interface LogAbstractInterface extends ModelInterface
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
     * Returns the value of the field "level"
     * Column: level
     * Attributes: NotNull | Numeric | Size(1)
     * @return mixed
     */
    public function getLevel(): mixed;
    
    /**
     * Sets the value of the field "level"
     * Column: level
     * Attributes: NotNull | Numeric | Size(1)
     * @param mixed $level
     * @return void
     */
    public function setLevel(mixed $level): void;
    
    /**
     * Returns the value of the field "type"
     * Column: type
     * Attributes: NotNull | Size('critical','alert','error','warning','notice','info','debug','emergency','other') | Type(18)
     * @return mixed
     */
    public function getType(): mixed;
    
    /**
     * Sets the value of the field "type"
     * Column: type
     * Attributes: NotNull | Size('critical','alert','error','warning','notice','info','debug','emergency','other') | Type(18)
     * @param mixed $type
     * @return void
     */
    public function setType(mixed $type): void;
    
    /**
     * Returns the value of the field "message"
     * Column: message
     * Attributes: NotNull | Type(6)
     * @return mixed
     */
    public function getMessage(): mixed;
    
    /**
     * Sets the value of the field "message"
     * Column: message
     * Attributes: NotNull | Type(6)
     * @param mixed $message
     * @return void
     */
    public function setMessage(mixed $message): void;
    
    /**
     * Returns the value of the field "context"
     * Column: context
     * Attributes: Type(24)
     * @return mixed
     */
    public function getContext(): mixed;
    
    /**
     * Sets the value of the field "context"
     * Column: context
     * Attributes: Type(24)
     * @param mixed $context
     * @return void
     */
    public function setContext(mixed $context): void;
    
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
}
