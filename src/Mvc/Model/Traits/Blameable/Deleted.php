<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Model\Traits\Blameable;

use PhalconKit\Db\Column;
use PhalconKit\Mvc\Model\Behavior\Transformable;
use PhalconKit\Mvc\Model\Interfaces\SoftDeleteInterface;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBehavior;
use PhalconKit\Mvc\Model\Traits\Abstracts\AbstractBlameable;
use PhalconKit\Mvc\Model\Traits\Identity;
use PhalconKit\Mvc\Model\Traits\Options;
use PhalconKit\Mvc\Model\Traits\Snapshot;
use PhalconKit\Mvc\Model\Traits\SoftDelete;
use PhalconKit\Mvc\ModelInterface;

trait Deleted
{
    use AbstractBehavior;
    use AbstractBlameable;
    use Options;
    use Identity;
    use Snapshot;
    use SoftDelete;
    use BlameAt;
    
    /**
     * Initializing Deleted
     */
    public function initializeDeleted(?array $options = null): void
    {
        $options ??= $this->getOptionsManager()->get('deleted') ?? [];
        
        $deletedField = $options['field'] ?? 'deleted';
        $deletedValue = $options['value'] ?? 1;
        
        $fieldBy = $options['fieldBy'] ?? 'deletedBy';
        $fieldAs = $options['fieldAs'] ?? 'deletedAs';
        $fieldAt = $options['fieldAt'] ?? 'deletedAt';
        
        $this->addUserRelationship($fieldBy, 'DeletedBy');
        $this->addUserRelationship($fieldAs, 'DeletedAs');
        
        $this->setDeletedBehavior(new Transformable([
            'beforeDelete' => [
                $fieldBy => $this->getCurrentUserIdCallback(),
                $fieldAs => $this->getCurrentUserIdCallback(true),
                $fieldAt => $this->getDateCallback(Column::DATETIME_FORMAT),
            ],
            'beforeValidationOnUpdate' => [
                $fieldBy => $this->hasChangedCallback(function (ModelInterface $model, string $field) use ($deletedField, $deletedValue): ?int {
                    $id = $model->readAttribute($field);
                    return $model->isDeleted($deletedField, $deletedValue)
                        ? $this->getCurrentUserIdCallback()()
                        : ($id === null || $id === '' || $id === 'NULL' ? null : (int)$id);
                }),
                $fieldAs => $this->hasChangedCallback(function (ModelInterface $model, string $field) use ($deletedField, $deletedValue): ?int {
                    $id = $model->readAttribute($field);
                    return $model->isDeleted($deletedField, $deletedValue)
                        ? $this->getCurrentUserIdCallback(true)()
                        : ($id === null || $id === '' || $id === 'NULL' ? null : (int)$id);
                }),
                $fieldAt => $this->hasChangedCallback(function (ModelInterface $model, string $field) use ($deletedField, $deletedValue): ?string {
                    return $model->isDeleted($deletedField, $deletedValue)
                        ? $this->getDateCallback(Column::DATETIME_FORMAT)()
                        : $model->readAttribute($field);
                }),
            ],
        ]));
    }
    
    /**
     * Set Deleted Behavior
     */
    public function setDeletedBehavior(Transformable $deletedBehavior): void
    {
        $this->setBehavior('deleted', $deletedBehavior);
    }
    
    /**
     * Get Deleted Behavior
     */
    public function getDeletedBehavior(): Transformable
    {
        $behavior = $this->getBehavior('deleted');
        assert($behavior instanceof Transformable);
        return $behavior;
    }
}
