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
 * @property AuditAbstractInterface $auditentity
 * @property AuditAbstractInterface $AuditEntity
 * @method AuditAbstractInterface getAuditEntity(?array $params = null)
 *
 * @property UserAbstractInterface $createdbyentity
 * @property UserAbstractInterface $CreatedByEntity
 * @method UserAbstractInterface getCreatedByEntity(?array $params = null)
 */
interface AuditDetailAbstractInterface extends ModelInterface
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
     * Returns the value of the field "auditId"
     * Column: audit_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getAuditId(): mixed;
    
    /**
     * Sets the value of the field "auditId"
     * Column: audit_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @param mixed $auditId
     * @return void
     */
    public function setAuditId(mixed $auditId): void;
    
    /**
     * Returns the value of the field "column"
     * Column: column
     * Attributes: NotNull | Size(64) | Type(2)
     * @return mixed
     */
    public function getColumn(): mixed;
    
    /**
     * Sets the value of the field "column"
     * Column: column
     * Attributes: NotNull | Size(64) | Type(2)
     * @param mixed $column
     * @return void
     */
    public function setColumn(mixed $column): void;
    
    /**
     * Returns the value of the field "before"
     * Column: before
     * Attributes: Type(23)
     * @return mixed
     */
    public function getBefore(): mixed;
    
    /**
     * Sets the value of the field "before"
     * Column: before
     * Attributes: Type(23)
     * @param mixed $before
     * @return void
     */
    public function setBefore(mixed $before): void;
    
    /**
     * Returns the value of the field "after"
     * Column: after
     * Attributes: Type(23)
     * @return mixed
     */
    public function getAfter(): mixed;
    
    /**
     * Sets the value of the field "after"
     * Column: after
     * Attributes: Type(23)
     * @param mixed $after
     * @return void
     */
    public function setAfter(mixed $after): void;
    
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
}
