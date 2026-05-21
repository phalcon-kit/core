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
use PhalconKit\Models\EmailFile;
use PhalconKit\Models\File;
use PhalconKit\Models\Template;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\EmailAbstractInterface;

/**
 * Class EmailAbstract
 *
 * This class defines a Email abstract model that extends the AbstractModel class and implements the EmailAbstractInterface.
 * It provides properties and methods for managing Email data.
 *
 * @property EmailFile[] $emailfilelist
 * @property EmailFile[] $EmailFileList
 * @method EmailFile[] getEmailFileList(?array $params = null)
 *
 * @property File[] $filelist
 * @property File[] $FileList
 * @method File[] getFileList(?array $params = null)
 *
 * @property Template $templateentity
 * @property Template $TemplateEntity
 * @method Template getTemplateEntity(?array $params = null)
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
abstract class EmailAbstract extends AbstractModel implements EmailAbstractInterface
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
     * Column: template_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $templateId = 0;
        
    /**
     * Column: from
     * Attributes: NotNull | Type(24)
     * @var mixed
     */
    public mixed $from = null;
        
    /**
     * Column: reply_to
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $replyTo = 'NULL';
        
    /**
     * Column: return_path
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $returnPath = 'NULL';
        
    /**
     * Column: read_receipt_to
     * Attributes: Size(255) | Type(2)
     * @var mixed
     */
    public mixed $readReceiptTo = 'NULL';
        
    /**
     * Column: priority
     * Attributes: NotNull | Numeric | Size(1) | Type(26)
     * @var mixed
     */
    public mixed $priority = 3;
        
    /**
     * Column: to
     * Attributes: NotNull | Type(24)
     * @var mixed
     */
    public mixed $to = null;
        
    /**
     * Column: cc
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $cc = 'NULL';
        
    /**
     * Column: bcc
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $bcc = 'NULL';
        
    /**
     * Column: subject
     * Attributes: NotNull | Size(255) | Type(2)
     * @var mixed
     */
    public mixed $subject = null;
        
    /**
     * Column: content
     * Attributes: NotNull | Type(23)
     * @var mixed
     */
    public mixed $content = null;
        
    /**
     * Column: meta
     * Attributes: Type(24)
     * @var mixed
     */
    public mixed $meta = 'NULL';
        
    /**
     * Column: sent
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @var mixed
     */
    public mixed $sent = 0;
        
    /**
     * Column: sent_at
     * Attributes: Type(4)
     * @var mixed
     */
    public mixed $sentAt = 'NULL';
        
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
     * Returns the value of the field "templateId"
     * Column: template_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getTemplateId(): mixed
    {
        return $this->templateId;
    }
    
    /**
     * Sets the value of the field "templateId"
     * Column: template_id
     * Attributes: Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $templateId
     * @return void
     */
    #[\Override]
    public function setTemplateId(mixed $templateId): void
    {
        $this->templateId = $templateId;
    }
    
    /**
     * Returns the value of the field "from"
     * Column: from
     * Attributes: NotNull | Type(24)
     * @return mixed
     */
    #[\Override]
    public function getFrom(): mixed
    {
        return $this->from;
    }
    
    /**
     * Sets the value of the field "from"
     * Column: from
     * Attributes: NotNull | Type(24)
     * @param mixed $from
     * @return void
     */
    #[\Override]
    public function setFrom(mixed $from): void
    {
        $this->from = $from;
    }
    
    /**
     * Returns the value of the field "replyTo"
     * Column: reply_to
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getReplyTo(): mixed
    {
        return $this->replyTo;
    }
    
    /**
     * Sets the value of the field "replyTo"
     * Column: reply_to
     * Attributes: Size(255) | Type(2)
     * @param mixed $replyTo
     * @return void
     */
    #[\Override]
    public function setReplyTo(mixed $replyTo): void
    {
        $this->replyTo = $replyTo;
    }
    
    /**
     * Returns the value of the field "returnPath"
     * Column: return_path
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getReturnPath(): mixed
    {
        return $this->returnPath;
    }
    
    /**
     * Sets the value of the field "returnPath"
     * Column: return_path
     * Attributes: Size(255) | Type(2)
     * @param mixed $returnPath
     * @return void
     */
    #[\Override]
    public function setReturnPath(mixed $returnPath): void
    {
        $this->returnPath = $returnPath;
    }
    
    /**
     * Returns the value of the field "readReceiptTo"
     * Column: read_receipt_to
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getReadReceiptTo(): mixed
    {
        return $this->readReceiptTo;
    }
    
    /**
     * Sets the value of the field "readReceiptTo"
     * Column: read_receipt_to
     * Attributes: Size(255) | Type(2)
     * @param mixed $readReceiptTo
     * @return void
     */
    #[\Override]
    public function setReadReceiptTo(mixed $readReceiptTo): void
    {
        $this->readReceiptTo = $readReceiptTo;
    }
    
    /**
     * Returns the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Size(1) | Type(26)
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
     * Attributes: NotNull | Numeric | Size(1) | Type(26)
     * @param mixed $priority
     * @return void
     */
    #[\Override]
    public function setPriority(mixed $priority): void
    {
        $this->priority = $priority;
    }
    
    /**
     * Returns the value of the field "to"
     * Column: to
     * Attributes: NotNull | Type(24)
     * @return mixed
     */
    #[\Override]
    public function getTo(): mixed
    {
        return $this->to;
    }
    
    /**
     * Sets the value of the field "to"
     * Column: to
     * Attributes: NotNull | Type(24)
     * @param mixed $to
     * @return void
     */
    #[\Override]
    public function setTo(mixed $to): void
    {
        $this->to = $to;
    }
    
    /**
     * Returns the value of the field "cc"
     * Column: cc
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getCc(): mixed
    {
        return $this->cc;
    }
    
    /**
     * Sets the value of the field "cc"
     * Column: cc
     * Attributes: Type(24)
     * @param mixed $cc
     * @return void
     */
    #[\Override]
    public function setCc(mixed $cc): void
    {
        $this->cc = $cc;
    }
    
    /**
     * Returns the value of the field "bcc"
     * Column: bcc
     * Attributes: Type(24)
     * @return mixed
     */
    #[\Override]
    public function getBcc(): mixed
    {
        return $this->bcc;
    }
    
    /**
     * Sets the value of the field "bcc"
     * Column: bcc
     * Attributes: Type(24)
     * @param mixed $bcc
     * @return void
     */
    #[\Override]
    public function setBcc(mixed $bcc): void
    {
        $this->bcc = $bcc;
    }
    
    /**
     * Returns the value of the field "subject"
     * Column: subject
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getSubject(): mixed
    {
        return $this->subject;
    }
    
    /**
     * Sets the value of the field "subject"
     * Column: subject
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $subject
     * @return void
     */
    #[\Override]
    public function setSubject(mixed $subject): void
    {
        $this->subject = $subject;
    }
    
    /**
     * Returns the value of the field "content"
     * Column: content
     * Attributes: NotNull | Type(23)
     * @return mixed
     */
    #[\Override]
    public function getContent(): mixed
    {
        return $this->content;
    }
    
    /**
     * Sets the value of the field "content"
     * Column: content
     * Attributes: NotNull | Type(23)
     * @param mixed $content
     * @return void
     */
    #[\Override]
    public function setContent(mixed $content): void
    {
        $this->content = $content;
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
     * Returns the value of the field "sent"
     * Column: sent
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @return mixed
     */
    #[\Override]
    public function getSent(): mixed
    {
        return $this->sent;
    }
    
    /**
     * Sets the value of the field "sent"
     * Column: sent
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @param mixed $sent
     * @return void
     */
    #[\Override]
    public function setSent(mixed $sent): void
    {
        $this->sent = $sent;
    }
    
    /**
     * Returns the value of the field "sentAt"
     * Column: sent_at
     * Attributes: Type(4)
     * @return mixed
     */
    #[\Override]
    public function getSentAt(): mixed
    {
        return $this->sentAt;
    }
    
    /**
     * Sets the value of the field "sentAt"
     * Column: sent_at
     * Attributes: Type(4)
     * @param mixed $sentAt
     * @return void
     */
    #[\Override]
    public function setSentAt(mixed $sentAt): void
    {
        $this->sentAt = $sentAt;
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
        $this->hasMany('id', EmailFile::class, 'emailId', ['alias' => 'EmailFileList']);

        $this->hasManyToMany(
            'id',
            EmailFile::class,
            'emailId',
            'fileId',
            File::class,
            'id',
            ['alias' => 'FileList']
        );

        $this->belongsTo('templateId', Template::class, 'id', ['alias' => 'TemplateEntity']);

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
        $this->addUnsignedIntValidation($validator, 'templateId', true);
        $this->addStringLengthValidation($validator, 'replyTo', 0, 255, true);
        $this->addStringLengthValidation($validator, 'returnPath', 0, 255, true);
        $this->addStringLengthValidation($validator, 'readReceiptTo', 0, 255, true);
        $this->addStringLengthValidation($validator, 'subject', 0, 255, false);
        $this->addUnsignedIntValidation($validator, 'sent', false);
        $this->addDateTimeValidation($validator, 'sentAt', true);
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
            'template_id' => 'templateId',
            'from' => 'from',
            'reply_to' => 'replyTo',
            'return_path' => 'returnPath',
            'read_receipt_to' => 'readReceiptTo',
            'priority' => 'priority',
            'to' => 'to',
            'cc' => 'cc',
            'bcc' => 'bcc',
            'subject' => 'subject',
            'content' => 'content',
            'meta' => 'meta',
            'sent' => 'sent',
            'sent_at' => 'sentAt',
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
