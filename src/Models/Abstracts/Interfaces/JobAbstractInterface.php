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
 *
 * @property UserAbstractInterface $updatedbyentity
 * @property UserAbstractInterface $UpdatedByEntity
 * @method UserAbstractInterface getUpdatedByEntity(?array $params = null)
 *
 * @property UserAbstractInterface $deletedbyentity
 * @property UserAbstractInterface $DeletedByEntity
 * @method UserAbstractInterface getDeletedByEntity(?array $params = null)
 */
interface JobAbstractInterface extends ModelInterface
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
     * Returns the value of the field "label"
     * Column: label
     * Attributes: Size(100) | Type(2)
     * @return mixed
     */
    public function getLabel(): mixed;
    
    /**
     * Sets the value of the field "label"
     * Column: label
     * Attributes: Size(100) | Type(2)
     * @param mixed $label
     * @return void
     */
    public function setLabel(mixed $label): void;
    
    /**
     * Returns the value of the field "task"
     * Column: task
     * Attributes: NotNull | Size(100) | Type(2)
     * @return mixed
     */
    public function getTask(): mixed;
    
    /**
     * Sets the value of the field "task"
     * Column: task
     * Attributes: NotNull | Size(100) | Type(2)
     * @param mixed $task
     * @return void
     */
    public function setTask(mixed $task): void;
    
    /**
     * Returns the value of the field "action"
     * Column: action
     * Attributes: NotNull | Size(100) | Type(2)
     * @return mixed
     */
    public function getAction(): mixed;
    
    /**
     * Sets the value of the field "action"
     * Column: action
     * Attributes: NotNull | Size(100) | Type(2)
     * @param mixed $action
     * @return void
     */
    public function setAction(mixed $action): void;
    
    /**
     * Returns the value of the field "params"
     * Column: params
     * Attributes: Type(24)
     * @return mixed
     */
    public function getParams(): mixed;
    
    /**
     * Sets the value of the field "params"
     * Column: params
     * Attributes: Type(24)
     * @param mixed $params
     * @return void
     */
    public function setParams(mixed $params): void;
    
    /**
     * Returns the value of the field "status"
     * Column: status
     * Attributes: NotNull | Size('new','progress','failed','finished') | Type(18)
     * @return mixed
     */
    public function getStatus(): mixed;
    
    /**
     * Sets the value of the field "status"
     * Column: status
     * Attributes: NotNull | Size('new','progress','failed','finished') | Type(18)
     * @param mixed $status
     * @return void
     */
    public function setStatus(mixed $status): void;
    
    /**
     * Returns the value of the field "result"
     * Column: result
     * Attributes: Type(24)
     * @return mixed
     */
    public function getResult(): mixed;
    
    /**
     * Sets the value of the field "result"
     * Column: result
     * Attributes: Type(24)
     * @param mixed $result
     * @return void
     */
    public function setResult(mixed $result): void;
    
    /**
     * Returns the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @return mixed
     */
    public function getPriority(): mixed;
    
    /**
     * Sets the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Unsigned | Size(1)
     * @param mixed $priority
     * @return void
     */
    public function setPriority(mixed $priority): void;
    
    /**
     * Returns the value of the field "runAt"
     * Column: run_at
     * Attributes: Type(4)
     * @return mixed
     */
    public function getRunAt(): mixed;
    
    /**
     * Sets the value of the field "runAt"
     * Column: run_at
     * Attributes: Type(4)
     * @param mixed $runAt
     * @return void
     */
    public function setRunAt(mixed $runAt): void;
    
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
