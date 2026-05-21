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
use PhalconKit\Models\Audit;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\AuditDetailAbstractInterface;

/**
 * Class AuditDetailAbstract
 *
 * This class defines a AuditDetail abstract model that extends the AbstractModel class and implements the AuditDetailAbstractInterface.
 * It provides properties and methods for managing AuditDetail data.
 *
 * @property Audit $auditentity
 * @property Audit $AuditEntity
 * @method Audit getAuditEntity(?array $params = null)
 *
 * @property User $createdbyentity
 * @property User $CreatedByEntity
 * @method User getCreatedByEntity(?array $params = null)
 */
abstract class AuditDetailAbstract extends AbstractModel implements AuditDetailAbstractInterface
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
     * Column: audit_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $auditId = null;
        
    /**
     * Column: column
     * Attributes: NotNull | Size(60) | Type(2)
     * @var mixed
     */
    public mixed $column = null;
        
    /**
     * Column: before
     * Attributes: Type(23)
     * @var mixed
     */
    public mixed $before = null;
        
    /**
     * Column: after
     * Attributes: Type(23)
     * @var mixed
     */
    public mixed $after = null;
        
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
     * Returns the value of the field "auditId"
     * Column: audit_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getAuditId(): mixed
    {
        return $this->auditId;
    }
    
    /**
     * Sets the value of the field "auditId"
     * Column: audit_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $auditId
     * @return void
     */
    #[\Override]
    public function setAuditId(mixed $auditId): void
    {
        $this->auditId = $auditId;
    }
    
    /**
     * Returns the value of the field "column"
     * Column: column
     * Attributes: NotNull | Size(60) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getColumn(): mixed
    {
        return $this->column;
    }
    
    /**
     * Sets the value of the field "column"
     * Column: column
     * Attributes: NotNull | Size(60) | Type(2)
     * @param mixed $column
     * @return void
     */
    #[\Override]
    public function setColumn(mixed $column): void
    {
        $this->column = $column;
    }
    
    /**
     * Returns the value of the field "before"
     * Column: before
     * Attributes: Type(23)
     * @return mixed
     */
    #[\Override]
    public function getBefore(): mixed
    {
        return $this->before;
    }
    
    /**
     * Sets the value of the field "before"
     * Column: before
     * Attributes: Type(23)
     * @param mixed $before
     * @return void
     */
    #[\Override]
    public function setBefore(mixed $before): void
    {
        $this->before = $before;
    }
    
    /**
     * Returns the value of the field "after"
     * Column: after
     * Attributes: Type(23)
     * @return mixed
     */
    #[\Override]
    public function getAfter(): mixed
    {
        return $this->after;
    }
    
    /**
     * Sets the value of the field "after"
     * Column: after
     * Attributes: Type(23)
     * @param mixed $after
     * @return void
     */
    #[\Override]
    public function setAfter(mixed $after): void
    {
        $this->after = $after;
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
     * Adds the default relationships to the model.
     * @return void
     */
    public function addDefaultRelationships(): void
    {
        $this->belongsTo('auditId', Audit::class, 'id', ['alias' => 'AuditEntity']);

        $this->belongsTo('createdBy', User::class, 'id', ['alias' => 'CreatedByEntity']);
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
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addUnsignedIntValidation($validator, 'auditId', false);
        $this->addStringLengthValidation($validator, 'column', 0, 60, false);
        $this->addUnsignedIntValidation($validator, 'deleted', false);
        $this->addDateTimeValidation($validator, 'createdAt', false);
        $this->addUnsignedIntValidation($validator, 'createdBy', true);
        
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
            'audit_id' => 'auditId',
            'column' => 'column',
            'before' => 'before',
            'after' => 'after',
            'deleted' => 'deleted',
            'created_at' => 'createdAt',
            'created_by' => 'createdBy',
        ];
    }
}
