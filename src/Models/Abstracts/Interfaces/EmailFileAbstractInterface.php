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
 * @property EmailAbstractInterface $emailentity
 * @property EmailAbstractInterface $EmailEntity
 * @method EmailAbstractInterface getEmailEntity(?array $params = null)
 *
 * @property FileAbstractInterface $fileentity
 * @property FileAbstractInterface $FileEntity
 * @method FileAbstractInterface getFileEntity(?array $params = null)
 *
 * @property UserAbstractInterface $createdbyentity
 * @property UserAbstractInterface $CreatedByEntity
 * @method UserAbstractInterface getCreatedByEntity(?array $params = null)
 */
interface EmailFileAbstractInterface extends ModelInterface
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
     * Returns the value of the field "emailId"
     * Column: email_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getEmailId(): mixed;
    
    /**
     * Sets the value of the field "emailId"
     * Column: email_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @param mixed $emailId
     * @return void
     */
    public function setEmailId(mixed $emailId): void;
    
    /**
     * Returns the value of the field "fileId"
     * Column: file_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @return mixed
     */
    public function getFileId(): mixed;
    
    /**
     * Sets the value of the field "fileId"
     * Column: file_id
     * Attributes: NotNull | Numeric | Unsigned | Type(14)
     * @param mixed $fileId
     * @return void
     */
    public function setFileId(mixed $fileId): void;
    
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
