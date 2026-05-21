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
use PhalconKit\Models\AuditDetail;
use PhalconKit\Models\Audit;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\AuditAbstractInterface;

/**
 * Class AuditAbstract
 *
 * This class defines a Audit abstract model that extends the AbstractModel class and implements the AuditAbstractInterface.
 * It provides properties and methods for managing Audit data.
 *
 * @property AuditDetail[] $auditdetaillist
 * @property AuditDetail[] $AuditDetailList
 * @method AuditDetail[] getAuditDetailList(?array $params = null)
 *
 * @property Audit $parententity
 * @property Audit $ParentEntity
 * @method Audit getParentEntity(?array $params = null)
 *
 * @property User $createdbyentity
 * @property User $CreatedByEntity
 * @method User getCreatedByEntity(?array $params = null)
 *
 * @property User $createdasentity
 * @property User $CreatedAsEntity
 * @method User getCreatedAsEntity(?array $params = null)
 */
abstract class AuditAbstract extends AbstractModel implements AuditAbstractInterface
{
    /**
     * Column: id
     * Attributes: First | Primary | NotNull | Numeric | Unsigned | AutoIncrement | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $id = null;
        
    /**
     * Column: parent_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $parentId = 0;
        
    /**
     * Column: uuid
     * Attributes: NotNull | Size(36) | Type(5)
     * @var mixed
     */
    public mixed $uuid = null;
        
    /**
     * Column: model
     * Attributes: NotNull | Size(255) | Type(2)
     * @var mixed
     */
    public mixed $model = null;
        
    /**
     * Column: table
     * Attributes: NotNull | Size(60) | Type(2)
     * @var mixed
     */
    public mixed $table = null;
        
    /**
     * Column: primary
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $primary = null;
        
    /**
     * Column: event
     * Attributes: NotNull | Size('create','update','delete','restore','other') | Type(18)
     * @var mixed
     */
    public mixed $event = '\'other\'';
        
    /**
     * Column: before
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $before = 'NULL';
        
    /**
     * Column: after
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $after = 'NULL';
        
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
     * Column: created_as
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $createdAs = 0;
    
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
     * Returns the value of the field "parentId"
     * Column: parent_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getParentId(): mixed
    {
        return $this->parentId;
    }
    
    /**
     * Sets the value of the field "parentId"
     * Column: parent_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $parentId
     * @return void
     */
    #[\Override]
    public function setParentId(mixed $parentId): void
    {
        $this->parentId = $parentId;
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
     * Returns the value of the field "model"
     * Column: model
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getModel(): mixed
    {
        return $this->model;
    }
    
    /**
     * Sets the value of the field "model"
     * Column: model
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $model
     * @return void
     */
    #[\Override]
    public function setModel(mixed $model): void
    {
        $this->model = $model;
    }
    
    /**
     * Returns the value of the field "table"
     * Column: table
     * Attributes: NotNull | Size(60) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getTable(): mixed
    {
        return $this->table;
    }
    
    /**
     * Sets the value of the field "table"
     * Column: table
     * Attributes: NotNull | Size(60) | Type(2)
     * @param mixed $table
     * @return void
     */
    #[\Override]
    public function setTable(mixed $table): void
    {
        $this->table = $table;
    }
    
    /**
     * Returns the value of the field "primary"
     * Column: primary
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getPrimary(): mixed
    {
        return $this->primary;
    }
    
    /**
     * Sets the value of the field "primary"
     * Column: primary
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $primary
     * @return void
     */
    #[\Override]
    public function setPrimary(mixed $primary): void
    {
        $this->primary = $primary;
    }
    
    /**
     * Returns the value of the field "event"
     * Column: event
     * Attributes: NotNull | Size('create','update','delete','restore','other') | Type(18)
     * @return mixed
     */
    #[\Override]
    public function getEvent(): mixed
    {
        return $this->event;
    }
    
    /**
     * Sets the value of the field "event"
     * Column: event
     * Attributes: NotNull | Size('create','update','delete','restore','other') | Type(18)
     * @param mixed $event
     * @return void
     */
    #[\Override]
    public function setEvent(mixed $event): void
    {
        $this->event = $event;
    }
    
    /**
     * Returns the value of the field "before"
     * Column: before
     * Attributes: Type(24)
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
     * Attributes: Type(24)
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
     * Attributes: Type(24)
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
     * Attributes: Type(24)
     * @param mixed $after
     * @return void
     */
    #[\Override]
    public function setAfter(mixed $after): void
    {
        $this->after = $after;
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
     * Returns the value of the field "createdAs"
     * Column: created_as
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getCreatedAs(): mixed
    {
        return $this->createdAs;
    }
    
    /**
     * Sets the value of the field "createdAs"
     * Column: created_as
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $createdAs
     * @return void
     */
    #[\Override]
    public function setCreatedAs(mixed $createdAs): void
    {
        $this->createdAs = $createdAs;
    }

    /**
     * Adds the default relationships to the model.
     * @return void
     */
    public function addDefaultRelationships(): void
    {
        $this->hasMany('id', AuditDetail::class, 'auditId', ['alias' => 'AuditDetailList']);

        $this->belongsTo('parentId', Audit::class, 'id', ['alias' => 'ParentEntity']);

        $this->belongsTo('createdBy', User::class, 'id', ['alias' => 'CreatedByEntity']);

        $this->belongsTo('createdAs', User::class, 'id', ['alias' => 'CreatedAsEntity']);
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
        $this->addUnsignedIntValidation($validator, 'parentId', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addStringLengthValidation($validator, 'model', 0, 255, false);
        $this->addStringLengthValidation($validator, 'table', 0, 60, false);
        $this->addUnsignedIntValidation($validator, 'primary', false);
        $this->addInclusionInValidation($validator, 'event', ['create','update','delete','restore','other'], false);
        $this->addDateTimeValidation($validator, 'createdAt', false);
        $this->addUnsignedIntValidation($validator, 'createdBy', true);
        $this->addUnsignedIntValidation($validator, 'createdAs', true);
        
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
            'parent_id' => 'parentId',
            'uuid' => 'uuid',
            'model' => 'model',
            'table' => 'table',
            'primary' => 'primary',
            'event' => 'event',
            'before' => 'before',
            'after' => 'after',
            'created_at' => 'createdAt',
            'created_by' => 'createdBy',
            'created_as' => 'createdAs',
        ];
    }
}
