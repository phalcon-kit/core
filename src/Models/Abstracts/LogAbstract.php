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
use PhalconKit\Models\Abstracts\Interfaces\LogAbstractInterface;

/**
 * Class LogAbstract
 *
 * This class defines a Log abstract model that extends the AbstractModel class and implements the LogAbstractInterface.
 * It provides properties and methods for managing Log data.
 *
 * @property User $createdbyentity
 * @property User $CreatedByEntity
 * @method User getCreatedByEntity(?array $params = null)
 */
abstract class LogAbstract extends AbstractModel implements LogAbstractInterface
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
     * Column: level
     * Attributes: NotNull | Numeric | Size(1)
     * @var mixed
     */
    public mixed $level = 0;
        
    /**
     * Column: type
     * Attributes: NotNull | Size('critical','alert','error','warning','notice','info','debug','emergency','other') | Type(18)
     * @var mixed
     */
    public mixed $type = '\'other\'';
        
    /**
     * Column: message
     * Attributes: NotNull | Type(6)
     * @var mixed
     */
    public mixed $message = null;
        
    /**
     * Column: context
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $context = null;
        
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
     * Returns the value of the field "level"
     * Column: level
     * Attributes: NotNull | Numeric | Size(1)
     * @return mixed
     */
    #[\Override]
    public function getLevel(): mixed
    {
        return $this->level;
    }
    
    /**
     * Sets the value of the field "level"
     * Column: level
     * Attributes: NotNull | Numeric | Size(1)
     * @param mixed $level
     * @return void
     */
    #[\Override]
    public function setLevel(mixed $level): void
    {
        $this->level = $level;
    }
    
    /**
     * Returns the value of the field "type"
     * Column: type
     * Attributes: NotNull | Size('critical','alert','error','warning','notice','info','debug','emergency','other') | Type(18)
     * @return mixed
     */
    #[\Override]
    public function getType(): mixed
    {
        return $this->type;
    }
    
    /**
     * Sets the value of the field "type"
     * Column: type
     * Attributes: NotNull | Size('critical','alert','error','warning','notice','info','debug','emergency','other') | Type(18)
     * @param mixed $type
     * @return void
     */
    #[\Override]
    public function setType(mixed $type): void
    {
        $this->type = $type;
    }
    
    /**
     * Returns the value of the field "message"
     * Column: message
     * Attributes: NotNull | Type(6)
     * @return mixed
     */
    #[\Override]
    public function getMessage(): mixed
    {
        return $this->message;
    }
    
    /**
     * Sets the value of the field "message"
     * Column: message
     * Attributes: NotNull | Type(6)
     * @param mixed $message
     * @return void
     */
    #[\Override]
    public function setMessage(mixed $message): void
    {
        $this->message = $message;
    }
    
    /**
     * Returns the value of the field "context"
     * Column: context
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getContext(): mixed
    {
        return $this->context;
    }
    
    /**
     * Sets the value of the field "context"
     * Column: context
     * Attributes: Type(24)
     * @param mixed $context
     * @return void
     */
    #[\Override]
    public function setContext(mixed $context): void
    {
        $this->context = $context;
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
        $this->addInclusionInValidation($validator, 'type', ['critical','alert','error','warning','notice','info','debug','emergency','other'], false);
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
            'level' => 'level',
            'type' => 'type',
            'message' => 'message',
            'context' => 'context',
            'created_at' => 'createdAt',
            'created_by' => 'createdBy',
        ];
    }
}
