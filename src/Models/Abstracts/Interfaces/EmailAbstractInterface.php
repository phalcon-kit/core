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
 * @property EmailFileAbstractInterface[] $emailfilelist
 * @property EmailFileAbstractInterface[] $EmailFileList
 * @method EmailFileAbstractInterface[] getEmailFileList(?array $params = null)
 *
 * @property FileAbstractInterface[] $filelist
 * @property FileAbstractInterface[] $FileList
 * @method FileAbstractInterface[] getFileList(?array $params = null)
 *
 * @property TemplateAbstractInterface $templateentity
 * @property TemplateAbstractInterface $TemplateEntity
 * @method TemplateAbstractInterface getTemplateEntity(?array $params = null)
 *
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
interface EmailAbstractInterface extends ModelInterface
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
     * Returns the value of the field "templateId"
     * Column: template_id
     * Attributes: Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getTemplateId(): mixed;
    
    /**
     * Sets the value of the field "templateId"
     * Column: template_id
     * Attributes: Numeric | Unsigned | Type(14)
     * @param mixed $templateId
     * @return void
     */
    public function setTemplateId(mixed $templateId): void;
    
    /**
     * Returns the value of the field "from"
     * Column: from
     * Attributes: NotNull | Type(24)
     * @return mixed
     */
    public function getFrom(): mixed;
    
    /**
     * Sets the value of the field "from"
     * Column: from
     * Attributes: NotNull | Type(24)
     * @param mixed $from
     * @return void
     */
    public function setFrom(mixed $from): void;
    
    /**
     * Returns the value of the field "replyTo"
     * Column: reply_to
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    public function getReplyTo(): mixed;
    
    /**
     * Sets the value of the field "replyTo"
     * Column: reply_to
     * Attributes: Size(255) | Type(2)
     * @param mixed $replyTo
     * @return void
     */
    public function setReplyTo(mixed $replyTo): void;
    
    /**
     * Returns the value of the field "returnPath"
     * Column: return_path
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    public function getReturnPath(): mixed;
    
    /**
     * Sets the value of the field "returnPath"
     * Column: return_path
     * Attributes: Size(255) | Type(2)
     * @param mixed $returnPath
     * @return void
     */
    public function setReturnPath(mixed $returnPath): void;
    
    /**
     * Returns the value of the field "readReceiptTo"
     * Column: read_receipt_to
     * Attributes: Size(255) | Type(2)
     * @return mixed
     */
    public function getReadReceiptTo(): mixed;
    
    /**
     * Sets the value of the field "readReceiptTo"
     * Column: read_receipt_to
     * Attributes: Size(255) | Type(2)
     * @param mixed $readReceiptTo
     * @return void
     */
    public function setReadReceiptTo(mixed $readReceiptTo): void;
    
    /**
     * Returns the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Size(1) | Type(26)
     * @return mixed
     */
    public function getPriority(): mixed;
    
    /**
     * Sets the value of the field "priority"
     * Column: priority
     * Attributes: NotNull | Numeric | Size(1) | Type(26)
     * @param mixed $priority
     * @return void
     */
    public function setPriority(mixed $priority): void;
    
    /**
     * Returns the value of the field "to"
     * Column: to
     * Attributes: NotNull | Type(24)
     * @return mixed
     */
    public function getTo(): mixed;
    
    /**
     * Sets the value of the field "to"
     * Column: to
     * Attributes: NotNull | Type(24)
     * @param mixed $to
     * @return void
     */
    public function setTo(mixed $to): void;
    
    /**
     * Returns the value of the field "cc"
     * Column: cc
     * Attributes: Type(24)
     * @return mixed
     */
    public function getCc(): mixed;
    
    /**
     * Sets the value of the field "cc"
     * Column: cc
     * Attributes: Type(24)
     * @param mixed $cc
     * @return void
     */
    public function setCc(mixed $cc): void;
    
    /**
     * Returns the value of the field "bcc"
     * Column: bcc
     * Attributes: Type(24)
     * @return mixed
     */
    public function getBcc(): mixed;
    
    /**
     * Sets the value of the field "bcc"
     * Column: bcc
     * Attributes: Type(24)
     * @param mixed $bcc
     * @return void
     */
    public function setBcc(mixed $bcc): void;
    
    /**
     * Returns the value of the field "subject"
     * Column: subject
     * Attributes: NotNull | Size(255) | Type(2)
     * @return mixed
     */
    public function getSubject(): mixed;
    
    /**
     * Sets the value of the field "subject"
     * Column: subject
     * Attributes: NotNull | Size(255) | Type(2)
     * @param mixed $subject
     * @return void
     */
    public function setSubject(mixed $subject): void;
    
    /**
     * Returns the value of the field "content"
     * Column: content
     * Attributes: NotNull | Type(23)
     * @return mixed
     */
    public function getContent(): mixed;
    
    /**
     * Sets the value of the field "content"
     * Column: content
     * Attributes: NotNull | Type(23)
     * @param mixed $content
     * @return void
     */
    public function setContent(mixed $content): void;
    
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
     * Returns the value of the field "sent"
     * Column: sent
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @return mixed
     */
    public function getSent(): mixed;
    
    /**
     * Sets the value of the field "sent"
     * Column: sent
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(26)
     * @param mixed $sent
     * @return void
     */
    public function setSent(mixed $sent): void;
    
    /**
     * Returns the value of the field "sentAt"
     * Column: sent_at
     * Attributes: Type(4)
     * @return mixed
     */
    public function getSentAt(): mixed;
    
    /**
     * Sets the value of the field "sentAt"
     * Column: sent_at
     * Attributes: Type(4)
     * @param mixed $sentAt
     * @return void
     */
    public function setSentAt(mixed $sentAt): void;
    
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
