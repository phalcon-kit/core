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
use PhalconKit\Models\Category;
use PhalconKit\Models\Flag;
use PhalconKit\Models\Page;
use PhalconKit\Models\SiteLang;
use PhalconKit\Models\Lang;
use PhalconKit\Models\Workspace;
use PhalconKit\Models\User;
use PhalconKit\Models\Abstracts\Interfaces\SiteAbstractInterface;

/**
 * Class SiteAbstract
 *
 * This class defines a Site abstract model that extends the AbstractModel class and implements the SiteAbstractInterface.
 * It provides properties and methods for managing Site data.
 *
 * @property Category[] $categorylist
 * @property Category[] $CategoryList
 * @method Category[] getCategoryList(?array $params = null)
 *
 * @property Flag[] $flaglist
 * @property Flag[] $FlagList
 * @method Flag[] getFlagList(?array $params = null)
 *
 * @property Page[] $pagelist
 * @property Page[] $PageList
 * @method Page[] getPageList(?array $params = null)
 *
 * @property SiteLang[] $sitelanglist
 * @property SiteLang[] $SiteLangList
 * @method SiteLang[] getSiteLangList(?array $params = null)
 *
 * @property Lang[] $langlist
 * @property Lang[] $LangList
 * @method Lang[] getLangList(?array $params = null)
 *
 * @property Workspace $workspaceentity
 * @property Workspace $WorkspaceEntity
 * @method Workspace getWorkspaceEntity(?array $params = null)
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
abstract class SiteAbstract extends AbstractModel implements SiteAbstractInterface
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
     * Column: workspace_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @var mixed
     */
    public mixed $workspaceId = null;
        
    /**
     * Column: label
     * Attributes: NotNull | Size(60) | Type(2)
     * @var mixed
     */
    public mixed $label = null;
        
    /**
     * Column: description
     * Attributes: Size(240) | Type(2)
     * @var mixed
     */
    public mixed $description = 'NULL';
        
    /**
     * Column: icon
     * Attributes: Size(64) | Type(2)
     * @var mixed
     */
    public mixed $icon = 'NULL';
        
    /**
     * Column: color
     * Attributes: Size(9) | Type(5)
     * @var mixed
     */
    public mixed $color = 'NULL';
        
    /**
     * Column: status
     * Attributes: NotNull | Size('active','inactive') | Type(18)
     * @var mixed
     */
    public mixed $status = '\'active\'';
        
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
     * Returns the value of the field "workspaceId"
     * Column: workspace_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @return mixed
     */
    #[\Override]
    public function getWorkspaceId(): mixed
    {
        return $this->workspaceId;
    }
    
    /**
     * Sets the value of the field "workspaceId"
     * Column: workspace_id
     * Attributes: NotNull | Numeric | Unsigned | Size(1) | Type(14)
     * @param mixed $workspaceId
     * @return void
     */
    #[\Override]
    public function setWorkspaceId(mixed $workspaceId): void
    {
        $this->workspaceId = $workspaceId;
    }
    
    /**
     * Returns the value of the field "label"
     * Column: label
     * Attributes: NotNull | Size(60) | Type(2)
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
     * Attributes: NotNull | Size(60) | Type(2)
     * @param mixed $label
     * @return void
     */
    #[\Override]
    public function setLabel(mixed $label): void
    {
        $this->label = $label;
    }
    
    /**
     * Returns the value of the field "description"
     * Column: description
     * Attributes: Size(240) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getDescription(): mixed
    {
        return $this->description;
    }
    
    /**
     * Sets the value of the field "description"
     * Column: description
     * Attributes: Size(240) | Type(2)
     * @param mixed $description
     * @return void
     */
    #[\Override]
    public function setDescription(mixed $description): void
    {
        $this->description = $description;
    }
    
    /**
     * Returns the value of the field "icon"
     * Column: icon
     * Attributes: Size(64) | Type(2)
     * @return mixed
     */
    #[\Override]
    public function getIcon(): mixed
    {
        return $this->icon;
    }
    
    /**
     * Sets the value of the field "icon"
     * Column: icon
     * Attributes: Size(64) | Type(2)
     * @param mixed $icon
     * @return void
     */
    #[\Override]
    public function setIcon(mixed $icon): void
    {
        $this->icon = $icon;
    }
    
    /**
     * Returns the value of the field "color"
     * Column: color
     * Attributes: Size(9) | Type(5)
     * @return mixed
     */
    #[\Override]
    public function getColor(): mixed
    {
        return $this->color;
    }
    
    /**
     * Sets the value of the field "color"
     * Column: color
     * Attributes: Size(9) | Type(5)
     * @param mixed $color
     * @return void
     */
    #[\Override]
    public function setColor(mixed $color): void
    {
        $this->color = $color;
    }
    
    /**
     * Returns the value of the field "status"
     * Column: status
     * Attributes: NotNull | Size('active','inactive') | Type(18)
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
     * Attributes: NotNull | Size('active','inactive') | Type(18)
     * @param mixed $status
     * @return void
     */
    #[\Override]
    public function setStatus(mixed $status): void
    {
        $this->status = $status;
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
        $this->hasMany('id', Category::class, 'siteId', ['alias' => 'CategoryList']);

        $this->hasMany('id', Flag::class, 'siteId', ['alias' => 'FlagList']);

        $this->hasMany('id', Page::class, 'siteId', ['alias' => 'PageList']);

        $this->hasMany('id', SiteLang::class, 'siteId', ['alias' => 'SiteLangList']);

        $this->hasManyToMany(
            'id',
            SiteLang::class,
            'siteId',
            'langId',
            Lang::class,
            'id',
            ['alias' => 'LangList']
        );

        $this->belongsTo('workspaceId', Workspace::class, 'id', ['alias' => 'WorkspaceEntity']);

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
        $this->addUnsignedIntValidation($validator, 'workspaceId', false);
        $this->addStringLengthValidation($validator, 'label', 0, 60, false);
        $this->addStringLengthValidation($validator, 'description', 0, 240, true);
        $this->addStringLengthValidation($validator, 'icon', 0, 64, true);
        $this->addStringLengthValidation($validator, 'color', 0, 9, true);
        $this->addInclusionInValidation($validator, 'status', ['active','inactive'], false);
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
            'workspace_id' => 'workspaceId',
            'label' => 'label',
            'description' => 'description',
            'icon' => 'icon',
            'color' => 'color',
            'status' => 'status',
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
