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
use PhalconKit\Models\Abstracts\Interfaces\JobAbstractInterface;

/**
 * Class JobAbstract
 *
 * This class defines a Job abstract model that extends the AbstractModel class and implements the JobAbstractInterface.
 * It provides properties and methods for managing Job data.
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
abstract class JobAbstract extends AbstractModel implements JobAbstractInterface
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
     * Column: label
     * Attributes: Size(100) | Type(2)
     * @var mixed
     */
    public mixed $label = null;
        
    /**
     * Column: task
     * Attributes: NotNull | Size(100) | Type(5)
     * @var mixed
     */
    public mixed $task = null;
        
    /**
     * Column: action
     * Attributes: NotNull | Size(100) | Type(5)
     * @var mixed
     */
    public mixed $action = null;
        
    /**
     * Column: params
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $params = null;
        
    /**
     * Column: status
     * Attributes: NotNull | Size('new','progress','failed','finished') | Type(18)
     * @var mixed
     */
    public mixed $status = '\'new\'';
        
    /**
     * Column: result
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $result = null;
        
    /**
     * Column: priority
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @var mixed
     */
    public mixed $priority = 0;
        
    /**
     * Column: run_at
     * Attributes: Type(4)
     * @var mixed
     */
    public mixed $runAt = null;
        
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
    public mixed $updatedBy = null;
        
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
    public mixed $deletedBy = null;
    
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
     * Returns the value of the field "label"
     * Column: label
     * Attributes: Size(100) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getLabel(): mixed
    {
        return $this->label;
    }
    
    /**
     * Sets the value of the field "label"
     * Column: label
     * Attributes: Size(100) | Type(2)
     * @param mixed $label
     * @return void
     */
    #[\Override]
    public function setLabel(mixed $label): void
    {
        $this->label = $label;
    }
    
    /**
     * Returns the value of the field "task"
     * Column: task
     * Attributes: NotNull | Size(100) | Type(5)
     * @return mixed
     */
    #[\Override]
    public function getTask(): mixed
    {
        return $this->task;
    }
    
    /**
     * Sets the value of the field "task"
     * Column: task
     * Attributes: NotNull | Size(100) | Type(5)
     * @param mixed $task
     * @return void
     */
    #[\Override]
    public function setTask(mixed $task): void
    {
        $this->task = $task;
    }
    
    /**
     * Returns the value of the field "action"
     * Column: action
     * Attributes: NotNull | Size(100) | Type(5)
     * @return mixed
     */
    #[\Override]
    public function getAction(): mixed
    {
        return $this->action;
    }
    
    /**
     * Sets the value of the field "action"
     * Column: action
     * Attributes: NotNull | Size(100) | Type(5)
     * @param mixed $action
     * @return void
     */
    #[\Override]
    public function setAction(mixed $action): void
    {
        $this->action = $action;
    }
    
    /**
     * Returns the value of the field "params"
     * Column: params
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getParams(): mixed
    {
        return $this->params;
    }
    
    /**
     * Sets the value of the field "params"
     * Column: params
     * Attributes: Type(24)
     * @param mixed $params
     * @return void
     */
    #[\Override]
    public function setParams(mixed $params): void
    {
        $this->params = $params;
    }
    
    /**
     * Returns the value of the field "status"
     * Column: status
     * Attributes: NotNull | Size('new','progress','failed','finished') | Type(18)
     * @return mixed
     */
    #[\Override]
    public function getStatus(): mixed
    {
        return $this->status;
    }
    
    /**
     * Sets the value of the field "status"
     * Column: status
     * Attributes: NotNull | Size('new','progress','failed','finished') | Type(18)
     * @param mixed $status
     * @return void
     */
    #[\Override]
    public function setStatus(mixed $status): void
    {
        $this->status = $status;
    }
    
    /**
     * Returns the value of the field "result"
     * Column: result
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getResult(): mixed
    {
        return $this->result;
    }
    
    /**
     * Sets the value of the field "result"
     * Column: result
     * Attributes: Type(24)
     * @param mixed $result
     * @return void
     */
    #[\Override]
    public function setResult(mixed $result): void
    {
        $this->result = $result;
    }
    
    /**
     * Returns the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @return mixed
     */
    #[\Override]
    public function getPriority(): mixed
    {
        return $this->priority;
    }
    
    /**
     * Sets the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @param mixed $priority
     * @return void
     */
    #[\Override]
    public function setPriority(mixed $priority): void
    {
        $this->priority = $priority;
    }
    
    /**
     * Returns the value of the field "runAt"
     * Column: run_at
     * Attributes: Type(4)
     * @return mixed
     */
    #[\Override]
    public function getRunAt(): mixed
    {
        return $this->runAt;
    }
    
    /**
     * Sets the value of the field "runAt"
     * Column: run_at
     * Attributes: Type(4)
     * @param mixed $runAt
     * @return void
     */
    #[\Override]
    public function setRunAt(mixed $runAt): void
    {
        $this->runAt = $runAt;
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
        $this->addUnsignedIntValidation($validator, 'id', true);
        $this->addStringLengthValidation($validator, 'uuid', 0, 36, false);
        $this->addStringLengthValidation($validator, 'label', 0, 100, true);
        $this->addStringLengthValidation($validator, 'task', 0, 100, false);
        $this->addStringLengthValidation($validator, 'action', 0, 100, false);
        $this->addInclusionInValidation($validator, 'status', ['new','progress','failed','finished'], false);
        $this->addUnsignedIntValidation($validator, 'priority', false);
        $this->addDateTimeValidation($validator, 'runAt', true);
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
            'label' => 'label',
            'task' => 'task',
            'action' => 'action',
            'params' => 'params',
            'status' => 'status',
            'result' => 'result',
            'priority' => 'priority',
            'run_at' => 'runAt',
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
